<?php

namespace App\Controllers\Api\v1;

use App\Controllers\Api\ApiController;
use Config\Database;

class AdminNotifications extends ApiController
{
    public function stats()
    {
        $db = Database::connect();
        $one = function ($sql) use ($db) {
            $q = $db->query($sql);
            if (!$q) return 0;
            $row = $q->getRowArray();
            return $row ? array_values($row)[0] : 0;
        };
        $all = function ($sql) use ($db) {
            $q = $db->query($sql);
            return $q ? $q->getResultArray() : [];
        };

        $totalSent      = (int) $one("SELECT COUNT(*) FROM user_notifications");
        $sentToday      = (int) $one("SELECT COUNT(*) FROM user_notifications WHERE DATE(created_at) = CURDATE()");
        $sentMonth      = (int) $one("SELECT COUNT(*) FROM user_notifications WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
        $readCount      = (int) $one("SELECT COUNT(*) FROM user_notifications WHERE is_read = 1");
        $readRate       = $totalSent > 0 ? round($readCount * 100 / $totalSent, 1) : 0;
        $optInUsers     = (int) $one("SELECT COUNT(*) FROM user_notification_settings WHERE daily_goal_reminder=1 OR achievement_notification=1 OR weekly_progress_report=1 OR tier_status_updates=1 OR community_challenges=1");

        $audiences = [
            'all'       => (int) $one("SELECT COUNT(*) FROM users"),
            'active'    => (int) $one("SELECT COUNT(DISTINCT user_id) FROM user_step_events WHERE DATE(is_date) >= (CURDATE() - INTERVAL 7 DAY)"),
            'inactive'  => (int) $one("SELECT COUNT(*) FROM users u WHERE NOT EXISTS(SELECT 1 FROM user_step_events se WHERE se.user_id = u.id AND DATE(se.is_date) >= (CURDATE() - INTERVAL 7 DAY))"),
            'walking'   => (int) $one("SELECT COUNT(DISTINCT user_id) FROM user_step_events WHERE event_time >= (NOW() - INTERVAL 5 MINUTE)"),
            'daily_goal'    => (int) $one("SELECT COUNT(*) FROM user_notification_settings WHERE daily_goal_reminder = 1"),
            'achievement'   => (int) $one("SELECT COUNT(*) FROM user_notification_settings WHERE achievement_notification = 1"),
            'weekly'        => (int) $one("SELECT COUNT(*) FROM user_notification_settings WHERE weekly_progress_report = 1"),
            'tier'          => (int) $one("SELECT COUNT(*) FROM user_notification_settings WHERE tier_status_updates = 1"),
            'challenges'    => (int) $one("SELECT COUNT(*) FROM user_notification_settings WHERE community_challenges = 1"),
        ];

        // Recent notifications, grouped by title+body+minute (one entry per campaign)
        $recent = $all("
            SELECT MAX(id) AS id, type, title, body,
                   COUNT(*) AS sent,
                   SUM(is_read) AS read_count,
                   MAX(created_at) AS last_at
            FROM user_notifications
            GROUP BY type, title, body, DATE_FORMAT(created_at,'%Y-%m-%d %H:%i')
            ORDER BY last_at DESC
            LIMIT 10
        ");

        return $this->success_response("Notifications.", [
            'kpi' => [
                'sent_total'   => $totalSent,
                'sent_today'   => $sentToday,
                'sent_month'   => $sentMonth,
                'read_rate'    => $readRate,
                'opt_in_users' => $optInUsers,
            ],
            'audiences' => $audiences,
            'recent'    => $recent,
        ]);
    }

    public function send()
    {
        $raw  = file_get_contents("php://input");
        $data = json_decode($raw, true) ?: [];

        $audience = trim($data['audience'] ?? 'all');
        $title    = trim($data['title'] ?? '');
        $body     = trim($data['body'] ?? '');
        $type     = trim($data['type'] ?? 'admin');

        if ($title === '' || $body === '') {
            return $this->error_response("Title and message are required.");
        }

        $db = Database::connect();
        $sqlByAud = [
            'all'        => "SELECT id FROM users",
            'active'     => "SELECT DISTINCT user_id AS id FROM user_step_events WHERE DATE(is_date) >= (CURDATE() - INTERVAL 7 DAY)",
            'inactive'   => "SELECT u.id FROM users u WHERE NOT EXISTS(SELECT 1 FROM user_step_events se WHERE se.user_id = u.id AND DATE(se.is_date) >= (CURDATE() - INTERVAL 7 DAY))",
            'walking'    => "SELECT DISTINCT user_id AS id FROM user_step_events WHERE event_time >= (NOW() - INTERVAL 5 MINUTE)",
            'daily_goal' => "SELECT user_id AS id FROM user_notification_settings WHERE daily_goal_reminder = 1",
            'achievement'=> "SELECT user_id AS id FROM user_notification_settings WHERE achievement_notification = 1",
            'weekly'     => "SELECT user_id AS id FROM user_notification_settings WHERE weekly_progress_report = 1",
            'tier'       => "SELECT user_id AS id FROM user_notification_settings WHERE tier_status_updates = 1",
            'challenges' => "SELECT user_id AS id FROM user_notification_settings WHERE community_challenges = 1",
        ];
        $sql = $sqlByAud[$audience] ?? $sqlByAud['all'];

        $q = $db->query($sql);
        $users = $q ? $q->getResultArray() : [];
        if (!$users) {
            return $this->error_response("No users match that audience.");
        }

        $now  = date('Y-m-d H:i:s');
        $rows = [];
        foreach ($users as $u) {
            $rows[] = [
                'user_id'    => (int) $u['id'],
                'type'       => $type,
                'title'      => $title,
                'body'       => $body,
                'meta'       => json_encode(['audience' => $audience, 'sent_by' => 'admin']),
                'is_read'    => 0,
                'created_at' => $now,
            ];
        }

        $db->table('user_notifications')->insertBatch($rows);

        // ---------- Push via FCM to each user's registered device tokens ----------
        $ids = array_column($users, 'id');
        $pushed = 0;
        $failed = 0;

        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $tokenQ = $db->query(
                "SELECT user_id, fcm_token
                 FROM user_fcm_tokens
                 WHERE user_id IN ($placeholders)
                   AND fcm_token IS NOT NULL
                   AND fcm_token != ''",
                $ids
            );
            $tokens = $tokenQ ? $tokenQ->getResultArray() : [];

            $fcm = new \App\Libraries\Fcm();
            if ($fcm->isReady()) {
                foreach ($tokens as $t) {
                    $deviceToken = trim($t['fcm_token']);
                    if ($deviceToken === '') {
                        continue;
                    }
                    $res = $fcm->send($deviceToken, $title, $body, [
                        'audience' => $audience,
                        'type'     => $type,
                        'user_id'  => (int) $t['user_id'],
                    ]);
                    if (!empty($res['status'])) {
                        $pushed++;
                    } else {
                        $failed++;
                        // Remove tokens FCM reports as unregistered/stale.
                        if ((int) ($res['code'] ?? 0) === 404) {
                            $db->query("DELETE FROM user_fcm_tokens WHERE fcm_token = ?", [$deviceToken]);
                        }
                    }
                }
            }
        }

        return $this->success_response("Notification queued.", [
            'audience'   => $audience,
            'recipients' => count($rows),
            'pushed'     => $pushed,
            'failed'     => $failed,
        ]);
    }

}
