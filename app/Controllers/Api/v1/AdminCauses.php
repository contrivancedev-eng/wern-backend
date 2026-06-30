<?php

namespace App\Controllers\Api\v1;

use App\Controllers\Api\ApiController;
use Config\Database;

class AdminCauses extends ApiController
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

        $activeCauses = (int) $one("SELECT COUNT(*) FROM cause_category");

        // Total across causes (cause_category.id = user_step_events.category_id for 1..N)
        $totalSteps   = (int) $one("
            SELECT COALESCE(SUM(se.steps),0)
            FROM user_step_events se
            JOIN cause_category cc ON cc.id = se.category_id
        ");
        $contributors = (int) $one("
            SELECT COUNT(DISTINCT se.user_id)
            FROM user_step_events se
            JOIN cause_category cc ON cc.id = se.category_id
        ");
        $litresWater  = (int) round($totalSteps / 10000);  // 1 litre per 10k steps (rough convention)

        // Per-cause breakdown
        $causes = $all("
            SELECT cc.id, cc.category_name AS name,
                   COALESCE(SUM(se.steps),0) AS steps,
                   COUNT(DISTINCT se.user_id) AS walkers,
                   COUNT(se.id) AS events
            FROM cause_category cc
            LEFT JOIN user_step_events se ON se.category_id = cc.id
            GROUP BY cc.id, cc.category_name
            ORDER BY cc.id
        ");

        return $this->success_response("Causes.", [
            'kpi' => [
                'active_causes' => $activeCauses,
                'total_steps'   => $totalSteps,
                'contributors'  => $contributors,
                'litres_water'  => $litresWater,
            ],
            'causes' => $causes,
        ]);
    }

    public function save()
    {
        $raw  = file_get_contents("php://input");
        $data = json_decode($raw, true) ?: [];
        $name = trim($data['name'] ?? '');
        $id   = (int) ($data['id'] ?? 0);

        if ($name === '') {
            return $this->error_response("Cause name is required.");
        }

        $db = Database::connect();
        if ($id > 0) {
            $db->table('cause_category')->where('id', $id)->update(['category_name' => $name]);
            return $this->success_response("Cause updated.", ['id' => $id, 'name' => $name]);
        }
        $db->table('cause_category')->insert(['category_name' => $name]);
        $newId = $db->insertID();
        return $this->success_response("Cause added.", ['id' => $newId, 'name' => $name]);
    }

    public function remove()
    {
        $raw  = file_get_contents("php://input");
        $data = json_decode($raw, true) ?: [];
        $id   = (int) ($data['id'] ?? 0);
        if ($id <= 0) return $this->error_response("Invalid id.");

        $db = Database::connect();
        $db->table('cause_category')->where('id', $id)->delete();
        return $this->success_response("Cause deleted.", ['id' => $id]);
    }
}
