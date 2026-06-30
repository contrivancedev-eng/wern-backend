<?php

namespace App\Controllers\Api\v1;

use App\Controllers\Api\ApiController;
use Config\Database;

class AdminTransactions extends ApiController
{
    public function stats()
    {
        $db = Database::connect();
        $one = function ($sql, $binds = []) use ($db) {
            $q = $db->query($sql, $binds);
            if (!$q) return 0;
            $row = $q->getRowArray();
            return $row ? array_values($row)[0] : 0;
        };
        $all = function ($sql, $binds = []) use ($db) {
            $q = $db->query($sql, $binds);
            return $q ? $q->getResultArray() : [];
        };

        // KPIs (earn-side aggregates, type=1 = earn)
        $totalEarned = (int) $one("SELECT COALESCE(SUM(points),0) FROM earn_litties WHERE type = 1");
        $totalSpent  = (int) $one("SELECT COALESCE(SUM(points),0) FROM earn_litties WHERE type = 2");
        $netCirc     = $totalEarned - $totalSpent;

        $byCat = $all("
            SELECT ec.id, ec.category_name AS name, COALESCE(SUM(el.points),0) AS total
            FROM earn_category ec
            LEFT JOIN earn_litties el ON el.earn_category_id = ec.id AND el.type = 1
            GROUP BY ec.id, ec.category_name
            ORDER BY total DESC
        ");

        // List (latest 200, joined with users for display)
        $list = $all("
            SELECT el.id, el.user_id, el.type, el.earn_category_id, el.points, el.description, el.date,
                   u.full_name, u.email, u.user_image,
                   ec.category_name
            FROM earn_litties el
            LEFT JOIN users u ON u.id = el.user_id
            LEFT JOIN earn_category ec ON ec.id = el.earn_category_id
            ORDER BY el.id DESC
            LIMIT 200
        ");

        // Referral/legacy points (for 'referral' filter, if used)
        $legacy = $all("
            SELECT p.id, p.user_id, p.type, p.points, p.description, p.create_at AS `date`,
                   u.full_name, u.email, u.user_image
            FROM points p
            LEFT JOIN users u ON u.id = p.user_id
            ORDER BY p.id DESC
            LIMIT 50
        ");

        return $this->success_response("Transactions.", [
            'kpi' => [
                'total_earned' => $totalEarned,
                'total_spent'  => $totalSpent,
                'net_circ'     => $netCirc,
                'by_category'  => $byCat, // also used for KPI cards per category
            ],
            'list'   => $list,
            'legacy' => $legacy,
        ]);
    }
}
