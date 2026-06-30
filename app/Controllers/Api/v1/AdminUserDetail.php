<?php

namespace App\Controllers\Api\v1;

use App\Controllers\Api\ApiController;
use Config\Database;

class AdminUserDetail extends ApiController
{
    public function stats()
    {
        $req = service('request');
        $id  = (int) $req->getGet('id');
        if ($id <= 0) return $this->error_response("Invalid user id.");

        $db = Database::connect();
        $one = function ($sql, $binds = []) use ($db) {
            $q = $db->query($sql, $binds);
            if (!$q) return 0;
            $row = $q->getRowArray();
            return $row ? array_values($row)[0] : 0;
        };
        $row = function ($sql, $binds = []) use ($db) {
            $q = $db->query($sql, $binds);
            return $q ? ($q->getRowArray() ?? []) : [];
        };
        $all = function ($sql, $binds = []) use ($db) {
            $q = $db->query($sql, $binds);
            return $q ? $q->getResultArray() : [];
        };

        $profile = $row("SELECT * FROM users WHERE id = ?", [$id]);
        if (!$profile) return $this->error_response("User not found.");

        // Core stats
        $totalSteps  = (int) $one("SELECT COALESCE(SUM(steps),0) FROM user_step_events WHERE user_id = ?", [$id]);
        $totalKm     = (float) $one("SELECT COALESCE(SUM(kilometre),0) FROM user_step_events WHERE user_id = ?", [$id]);
        $totalKcal   = (float) $one("SELECT COALESCE(SUM(kcal),0) FROM user_step_events WHERE user_id = ?", [$id]);
        $totalEvents = (int) $one("SELECT COUNT(*) FROM user_step_events WHERE user_id = ?", [$id]);

        $earned      = (int) $one("SELECT COALESCE(SUM(points),0) FROM earn_litties WHERE user_id = ? AND type = 1", [$id]);
        $spent       = (int) $one("SELECT COALESCE(SUM(points),0) FROM earn_litties WHERE user_id = ? AND type = 2", [$id]);
        $balance     = $earned - $spent;

        $refCount    = (int) $one("SELECT COUNT(*) FROM users WHERE referral_user_id = ?", [$id]);
        $referrer    = $row("SELECT id, full_name, email, referal_code FROM users WHERE id = ?", [(int) $profile['referral_user_id']]);

        $streakRow   = $row("SELECT claim_day, last_claim_date FROM user_daily_claim_status WHERE user_id = ? ORDER BY id DESC LIMIT 1", [$id]);
        $claimCount  = (int) $one("SELECT COUNT(*) FROM user_daily_claim_status WHERE user_id = ?", [$id]);

        $goals       = $row("SELECT daily_step_goal, activity_level, weekly_goal FROM user_goals WHERE user_id = ? ORDER BY id DESC LIMIT 1", [$id]);

        // Steps by day (last 30 days)
        $stepsTrend = $all("
            SELECT DATE(is_date) AS d, COALESCE(SUM(steps),0) AS steps,
                   COALESCE(SUM(kilometre),0) AS km
            FROM user_step_events
            WHERE user_id = ? AND DATE(is_date) >= (CURDATE() - INTERVAL 29 DAY)
            GROUP BY DATE(is_date) ORDER BY d
        ", [$id]);

        // Litties trend (last 30 days)
        $littiesTrend = $all("
            SELECT DATE(`date`) AS d,
                   SUM(CASE WHEN type=1 THEN points ELSE 0 END) AS earned,
                   SUM(CASE WHEN type=2 THEN points ELSE 0 END) AS spent
            FROM earn_litties
            WHERE user_id = ? AND DATE(`date`) >= (CURDATE() - INTERVAL 29 DAY)
            GROUP BY DATE(`date`) ORDER BY d
        ", [$id]);

        // Cause breakdown (user_step_events.category_id → cause_category)
        $causeBreakdown = $all("
            SELECT cc.id, cc.category_name AS name, COALESCE(SUM(se.steps),0) AS steps, COUNT(se.id) AS events
            FROM cause_category cc
            LEFT JOIN user_step_events se ON se.category_id = cc.id AND se.user_id = ?
            GROUP BY cc.id, cc.category_name
            ORDER BY steps DESC
        ", [$id]);

        // Peak hours (user's walking distribution)
        $peakHours = $all("
            SELECT HOUR(event_time) AS h, COUNT(*) AS c, COALESCE(SUM(steps),0) AS steps
            FROM user_step_events WHERE user_id = ? AND event_time IS NOT NULL
            GROUP BY HOUR(event_time) ORDER BY h
        ", [$id]);

        // Recent Litties txns
        $recentTx = $all("
            SELECT el.id, el.type, el.points, el.description, el.date,
                   ec.category_name
            FROM earn_litties el
            LEFT JOIN earn_category ec ON ec.id = el.earn_category_id
            WHERE el.user_id = ?
            ORDER BY el.id DESC LIMIT 20
        ", [$id]);

        // Referred users brought in
        $referredUsers = $all("
            SELECT id, full_name, email, create_on, status
            FROM users WHERE referral_user_id = ?
            ORDER BY id DESC LIMIT 20
        ", [$id]);

        // GPS cluster for this user (last 30d)
        $gpsClusters = $all("
            SELECT ROUND(latitude,2) AS lat, ROUND(longitude,2) AS lng,
                   COUNT(*) AS events, SUM(steps) AS steps
            FROM user_step_events
            WHERE user_id = ? AND latitude IS NOT NULL AND longitude IS NOT NULL
              AND DATE(is_date) >= (CURDATE() - INTERVAL 29 DAY)
            GROUP BY ROUND(latitude,2), ROUND(longitude,2)
            ORDER BY events DESC LIMIT 30
        ", [$id]);

        return $this->success_response("User detail.", [
            'profile'       => [
                'id'                     => $profile['id'],
                'full_name'              => $profile['full_name'],
                'nickname'               => $profile['nickname'] ?? '',
                'email'                  => $profile['email'],
                'country_code'           => $profile['country_code'],
                'phone_number'           => $profile['phone_number'],
                'membership_card_number' => $profile['membership_card_number'] ?? '',
                'referal_code'           => $profile['referal_code'] ?? '',
                'user_image'             => $profile['user_image'] ?? '',
                'card_points'            => (int) ($profile['card_points'] ?? 0),
                'status'                 => (int) ($profile['status'] ?? 0),
                'create_on'              => $profile['create_on'],
                'referral_user_id'       => (int) ($profile['referral_user_id'] ?? 0),
            ],
            'kpi'           => [
                'balance'        => $balance,
                'earned'         => $earned,
                'spent'          => $spent,
                'total_steps'    => $totalSteps,
                'total_km'       => round($totalKm, 2),
                'total_kcal'     => round($totalKcal, 2),
                'total_events'   => $totalEvents,
                'referrals_made' => $refCount,
                'streak_day'     => (int) ($streakRow['claim_day']  ?? 0),
                'last_claim'     => $streakRow['last_claim_date']   ?? null,
                'claim_count'    => $claimCount,
            ],
            'goals'          => $goals,
            'referrer'       => $referrer ?: null,
            'steps_trend'    => $stepsTrend,
            'litties_trend'  => $littiesTrend,
            'cause_breakdown'=> $causeBreakdown,
            'peak_hours'     => $peakHours,
            'recent_tx'      => $recentTx,
            'referred_users' => $referredUsers,
            'gps_clusters'   => $gpsClusters,
        ]);
    }
}
