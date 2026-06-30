<?php

namespace App\Controllers\Api\v1;

use App\Controllers\Api\ApiController;
use Config\Database;

class AdminReviews extends ApiController
{
    /**
     * Admin API — returns KPIs + star distribution + all reviews (user_id wise with user info).
     */
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

        // ---------- KPIs ----------
        $totalReviews = (int)   $one("SELECT COUNT(*) FROM user_reviews");
        $avgRating    = (float) $one("SELECT IFNULL(ROUND(AVG(star), 2), 0) FROM user_reviews");
        $withText     = (int)   $one("SELECT COUNT(*) FROM user_reviews WHERE response IS NOT NULL AND response != ''");
        $today        = (int)   $one("SELECT COUNT(*) FROM user_reviews WHERE DATE(created_at) = CURDATE()");

        // ---------- Star distribution ----------
        $distRows = $all("SELECT star, COUNT(*) AS c FROM user_reviews GROUP BY star");
        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($distRows as $r) {
            $s = (int) $r['star'];
            if ($s >= 1 && $s <= 5) {
                $distribution[$s] = (int) $r['c'];
            }
        }

        // ---------- Reviews list (user_id wise, newest first) ----------
        $reviews = $all("
            SELECT r.id, r.user_id, r.star, r.response, r.created_at,
                   u.full_name, u.email, u.user_image
            FROM user_reviews r
            LEFT JOIN users u ON u.id = r.user_id
            ORDER BY r.id DESC
        ");

        return $this->success_response("Reviews.", [
            'kpi' => [
                'total_reviews'   => $totalReviews,
                'average_rating'  => $avgRating,
                'with_response'   => $withText,
                'today'           => $today,
            ],
            'distribution' => $distribution,
            'reviews'      => $reviews,
        ]);
    }

    /**
     * Admin API — all reviews for a single user (view page).
     * Input: user_id (GET)
     */
    public function by_user()
    {
        $user_id = (int) $this->input_get('user_id');
        if ($user_id <= 0) {
            return $this->error_response("user_id is required.");
        }

        $db = Database::connect();

        // ---------- User profile ----------
        $userRow = $db->query(
            "SELECT id, full_name, email, phone_number, country_code, user_image, membership_card_number,
                    referal_code, create_on
             FROM users
             WHERE id = ?",
            [$user_id]
        );
        $user = $userRow ? $userRow->getRowArray() : null;
        if (!$user) {
            return $this->error_response("User not found.");
        }

        // ---------- Reviews by this user ----------
        $reviewsQ = $db->query(
            "SELECT id, user_id, star, response, created_at
             FROM user_reviews
             WHERE user_id = ?
             ORDER BY id DESC",
            [$user_id]
        );
        $reviews = $reviewsQ ? $reviewsQ->getResultArray() : [];

        // ---------- Stats for this user ----------
        $total    = count($reviews);
        $withText = 0;
        $sumStar  = 0;
        $dist     = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($reviews as $r) {
            $s = (int) $r['star'];
            if ($s >= 1 && $s <= 5) $dist[$s]++;
            $sumStar += $s;
            if (!empty($r['response']) && trim($r['response']) !== '') $withText++;
        }
        $avg = $total > 0 ? round($sumStar / $total, 2) : 0;

        return $this->success_response("User reviews.", [
            'user'         => $user,
            'kpi'          => [
                'total_reviews'  => $total,
                'average_rating' => $avg,
                'with_response'  => $withText,
            ],
            'distribution' => $dist,
            'reviews'      => $reviews,
        ]);
    }
}
