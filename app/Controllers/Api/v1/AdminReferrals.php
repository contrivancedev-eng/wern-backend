<?php

namespace App\Controllers\Api\v1;

use App\Controllers\Api\ApiController;
use Config\Database;

class AdminReferrals extends ApiController
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

        // KPIs
        $totalReferrals   = (int) $one("SELECT COUNT(*) FROM users WHERE referral_user_id > 0");
        $activeReferrers  = (int) $one("SELECT COUNT(DISTINCT referral_user_id) FROM users WHERE referral_user_id > 0");
        $totalUsers       = (int) $one("SELECT COUNT(*) FROM users");
        $conversionRate   = $totalUsers > 0 ? round($totalReferrals * 100 / $totalUsers, 1) : 0;
        $newThisMonth     = (int) $one("SELECT COUNT(*) FROM users WHERE referral_user_id > 0 AND create_on >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");

        // Litties from referral signups: counted as Sign Up earn rows for users whose referral_user_id > 0
        $littiesEarned = (int) $one("
            SELECT COALESCE(SUM(el.points),0)
            FROM earn_litties el
            JOIN users u ON u.id = el.user_id
            WHERE el.type = 1 AND el.earn_category_id = 1 AND u.referral_user_id > 0
        ");

        // Top referrers
        $top = $all("
            SELECT u.id, u.full_name, u.email, u.user_image, u.referal_code,
                   COUNT(r.id) AS referral_count,
                   COALESCE((
                      SELECT SUM(el.points)
                      FROM earn_litties el
                      JOIN users ru ON ru.id = el.user_id
                      WHERE el.type=1 AND el.earn_category_id=1 AND ru.referral_user_id = u.id
                   ),0) AS litties_earned
            FROM users u
            JOIN users r ON r.referral_user_id = u.id
            GROUP BY u.id, u.full_name, u.email, u.user_image, u.referal_code
            ORDER BY referral_count DESC
            LIMIT 10
        ");

        // Recent referrals (latest signups that came via referral_user_id)
        $recent = $all("
            SELECT r.id, r.full_name, r.email, r.user_image, r.create_on,
                   r.referral_user_id AS referrer_id,
                   u.full_name AS referrer_name,
                   u.referal_code AS referrer_code
            FROM users r
            LEFT JOIN users u ON u.id = r.referral_user_id
            WHERE r.referral_user_id > 0
            ORDER BY r.id DESC
            LIMIT 20
        ");

        // All referral codes from `users` with usage count
        $codes = $all("
            SELECT u.id, u.full_name, u.email, u.user_image, u.referal_code,
                   COUNT(r.id) AS uses,
                   COALESCE((
                      SELECT SUM(el.points)
                      FROM earn_litties el
                      JOIN users ru ON ru.id = el.user_id
                      WHERE el.type=1 AND el.earn_category_id=1 AND ru.referral_user_id = u.id
                   ),0) AS litties_earned,
                   u.status
            FROM users u
            LEFT JOIN users r ON r.referral_user_id = u.id
            WHERE u.referal_code IS NOT NULL AND u.referal_code != ''
            GROUP BY u.id, u.full_name, u.email, u.user_image, u.referal_code, u.status
            ORDER BY uses DESC, u.id DESC
            LIMIT 200
        ");

        return $this->success_response("Referrals.", [
            'kpi' => [
                'total_referrals'  => $totalReferrals,
                'conversion_rate'  => $conversionRate,
                'litties_earned'   => $littiesEarned,
                'active_referrers' => $activeReferrers,
                'new_this_month'   => $newThisMonth,
            ],
            'top'    => $top,
            'recent' => $recent,
            'codes'  => $codes,
        ]);
    }
}
