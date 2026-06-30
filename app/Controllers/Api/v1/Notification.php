<?php
namespace App\Controllers\Api\v1;

use App\Controllers\Api\ApiController;
use App\Models\Operation;

class Notification extends ApiController
{
    const TOGGLES = [
        'daily_goal_reminder',
        'achievement_notification',
        'weekly_progress_report',
        'tier_status_updates',
        'community_challenges',
    ];

    /* ===================== GET SETTINGS ===================== */
    public function get_notification_settings()
    {
        $operation = new Operation();

        $token = $this->input_get('token');
        if (!$token) return $this->error_response("Token is required.");

        $jwtData = $operation->get_user_id_from_token($token);
        if (!$jwtData['status']) return $this->error_response($jwtData['error']);

        $user_id = (int)$jwtData['user_id'];

        $row = $this->ensure_settings_row($operation, $user_id);

        $data = ['user_id' => $user_id];
        foreach (self::TOGGLES as $field) {
            $data[$field] = (bool)(int)($row->$field ?? 1);
        }

        return $this->success_response("Notification settings fetched", $data);
    }

    /* ===================== UPDATE SETTINGS ===================== */
    public function update_notification_settings()
    {
        $operation = new Operation();

        $token = $this->input_post('token');
        if (!$token) return $this->error_response("Token is required.");

        $jwtData = $operation->get_user_id_from_token($token);
        if (!$jwtData['status']) return $this->error_response($jwtData['error']);

        $user_id = (int)$jwtData['user_id'];
        $this->ensure_settings_row($operation, $user_id);

        $update = [];
        foreach (self::TOGGLES as $field) {
            $v = $this->input_post($field);
            if ($v === null || $v === '') continue;
            $update[$field] = $this->to_bool_int($v);
        }

        if (empty($update)) {
            return $this->error_response("No toggle fields provided. Send at least one of: " . implode(', ', self::TOGGLES));
        }

        $operation->update_data('user_notification_settings', ['user_id' => $user_id], $update);

        $row = $this->ensure_settings_row($operation, $user_id);
        $data = ['user_id' => $user_id];
        foreach (self::TOGGLES as $field) {
            $data[$field] = (bool)(int)($row->$field ?? 1);
        }

        return $this->success_response("Notification settings updated", $data);
    }

    /* ===================== GET INBOX ===================== */
    public function get_notifications()
    {
        $operation = new Operation();

        $token = $this->input_get('token');
        if (!$token) return $this->error_response("Token is required.");

        $jwtData = $operation->get_user_id_from_token($token);
        if (!$jwtData['status']) return $this->error_response($jwtData['error']);

        $user_id = (int)$jwtData['user_id'];

        $limit  = (int)($this->input_get('limit')  ?: 50);
        $offset = (int)($this->input_get('offset') ?: 0);

        $rs = $operation->db->query(
            "SELECT * FROM user_notifications
             WHERE user_id = ?
             ORDER BY id DESC
             LIMIT {$limit} OFFSET {$offset}",
            [$user_id]
        )->getResult();

        $unread = $operation->db->query(
            "SELECT COUNT(*) AS c FROM user_notifications WHERE user_id = ? AND is_read = 0",
            [$user_id]
        )->getRow();

        $list = [];
        foreach ($rs as $r) {
            $list[] = [
                'id'         => (int)$r->id,
                'type'       => $r->type,
                'title'      => $r->title,
                'body'       => $r->body,
                'meta'       => $r->meta ? json_decode($r->meta, true) : null,
                'is_read'    => (bool)(int)$r->is_read,
                'created_at' => $r->created_at,
            ];
        }

        return $this->success_response("Notifications fetched", [
            'unread_count'  => (int)($unread->c ?? 0),
            'notifications' => $list,
        ]);
    }

    /* ===================== MARK READ ===================== */
    public function mark_notification_read()
    {
        $operation = new Operation();

        $token = $this->input_post('token');
        if (!$token) return $this->error_response("Token is required.");

        $jwtData = $operation->get_user_id_from_token($token);
        if (!$jwtData['status']) return $this->error_response($jwtData['error']);

        $user_id = (int)$jwtData['user_id'];
        $id      = (int)$this->input_post('id');      // optional: mark single
        $all     = $this->input_post('all');          // optional: mark all

        if ($all) {
            $operation->db->query(
                "UPDATE user_notifications SET is_read = 1 WHERE user_id = ?",
                [$user_id]
            );
            return $this->success_response("All notifications marked read");
        }

        if ($id <= 0) {
            return $this->error_response("Provide 'id' or set 'all=1'.");
        }

        $operation->db->query(
            "UPDATE user_notifications SET is_read = 1 WHERE id = ? AND user_id = ?",
            [$id, $user_id]
        );

        return $this->success_response("Notification marked read");
    }

    /* =====================================================
     * HELPER: send a notification if the user has its toggle ON.
     * Call this from anywhere (e.g. Step::save_step_event).
     * Returns true if stored, false if blocked by user setting.
     * =====================================================*/
    public static function send($user_id, $type, $title, $body, $meta = null)
    {
        if (!in_array($type, self::TOGGLES, true)) return false;

        $operation = new Operation();
        $db = $operation->db;

        // Ensure settings row exists (default all ON)
        $exists = $db->query(
            "SELECT `{$type}` AS enabled FROM user_notification_settings WHERE user_id = ?",
            [$user_id]
        )->getRow();

        if (!$exists) {
            $db->query(
                "INSERT INTO user_notification_settings (user_id) VALUES (?)",
                [$user_id]
            );
            $enabled = 1;
        } else {
            $enabled = (int)$exists->enabled;
        }

        if ($enabled !== 1) return false; // user turned this notification OFF

        $db->query(
            "INSERT INTO user_notifications (user_id, type, title, body, meta)
             VALUES (?, ?, ?, ?, ?)",
            [
                $user_id,
                $type,
                $title,
                $body,
                $meta !== null ? json_encode($meta) : null
            ]
        );

        // Deliver the push to the user's devices via FCM.
        $data = is_array($meta) ? $meta : [];
        $data['type'] = $type;
        self::push_fcm($user_id, $title, $body, $data);

        return true;
    }

    /* =====================================================
     * Save / refresh a device's FCM token (called by the app).
     * POST save-fcm-token  { token (JWT), fcm_token, platform? }
     * =====================================================*/
    public function save_fcm_token()
    {
        $operation = new Operation();

        $token = $this->input_post('token') ?: ($this->json_body()['token'] ?? '');
        if (!$token) return $this->error_response("Token is required.");

        $jwtData = $operation->get_user_id_from_token($token);
        if (!$jwtData['status']) return $this->error_response($jwtData['error']);

        $user_id = (int) $jwtData['user_id'];

        $fcm_token = trim((string) ($this->input_post('fcm_token') ?: ($this->json_body()['fcm_token'] ?? '')));
        $platform  = trim((string) ($this->input_post('platform')  ?: ($this->json_body()['platform']  ?? '')));

        if ($fcm_token === '') return $this->error_response("fcm_token is required.");

        $db = $operation->db;
        $exists = $db->query("SELECT id FROM user_fcm_tokens WHERE fcm_token = ?", [$fcm_token])->getRow();

        if ($exists) {
            $db->query(
                "UPDATE user_fcm_tokens SET user_id = ?, platform = ?, updated_at = NOW() WHERE fcm_token = ?",
                [$user_id, $platform !== '' ? $platform : null, $fcm_token]
            );
        } else {
            $db->query(
                "INSERT INTO user_fcm_tokens (user_id, fcm_token, platform, created_at, updated_at)
                 VALUES (?, ?, ?, NOW(), NOW())",
                [$user_id, $fcm_token, $platform !== '' ? $platform : null]
            );
        }

        return $this->success_response("FCM token saved.", [
            "user_id"  => $user_id,
            "platform" => $platform,
        ]);
    }

    /* =====================================================
     * Push a message to every device a user has registered.
     * Stale tokens (FCM 404 / UNREGISTERED) are removed automatically.
     * =====================================================*/
    public static function push_fcm($user_id, $title, $body, $data = [])
    {
        $operation = new Operation();
        $rows = $operation->db->query(
            "SELECT fcm_token FROM user_fcm_tokens WHERE user_id = ?",
            [$user_id]
        )->getResult();

        if (empty($rows)) return;

        $fcm = new \App\Libraries\Fcm();
        if (!$fcm->isReady()) return;

        foreach ($rows as $r) {
            $res = $fcm->send($r->fcm_token, $title, $body, is_array($data) ? $data : []);
            if ((int) ($res['code'] ?? 0) === 404) {
                $operation->db->query("DELETE FROM user_fcm_tokens WHERE fcm_token = ?", [$r->fcm_token]);
            }
        }
    }

    private function json_body()
    {
        static $decoded = null;
        if ($decoded !== null) return $decoded;

        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /* ===================== PRIVATE HELPERS ===================== */
    private function ensure_settings_row($operation, $user_id)
    {
        $row = $operation->db->query(
            "SELECT * FROM user_notification_settings WHERE user_id = ?",
            [$user_id]
        )->getRow();

        if (!$row) {
            $operation->db->query(
                "INSERT INTO user_notification_settings (user_id) VALUES (?)",
                [$user_id]
            );
            $row = $operation->db->query(
                "SELECT * FROM user_notification_settings WHERE user_id = ?",
                [$user_id]
            )->getRow();
        }
        return $row;
    }

    private function to_bool_int($v)
    {
        if ($v === true || $v === 'true' || $v === '1' || $v === 1 || $v === 'on') return 1;
        return 0;
    }
}
