<?php

namespace App\Controllers\Api\v1;

use App\Controllers\Api\ApiController;
use Config\Database;

class AdminRewards extends ApiController
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
        $rowOf = function ($sql) use ($db) {
            $q = $db->query($sql);
            return $q ? ($q->getRowArray() ?? []) : [];
        };

        // KPIs
        $claimsToday    = (int) $one("SELECT COUNT(*) FROM user_daily_claim_status WHERE DATE(last_claim_date) = CURDATE()");
        $littiesToday   = (int) $one("SELECT COALESCE(SUM(points),0) FROM earn_litties WHERE DATE(`date`) = CURDATE() AND type = 1");
        $totalUsers     = (int) $one("SELECT COUNT(*) FROM users");
        $claimRate      = $totalUsers > 0 ? round($claimsToday * 100 / $totalUsers, 1) : 0;
        $avgStreak      = (float) $one("SELECT COALESCE(AVG(CAST(claim_day AS UNSIGNED)),0) FROM user_daily_claim_status");

        // Config rows
        $daily     = $rowOf("SELECT * FROM sys_daily_bonus ORDER BY id ASC LIMIT 1");
        $sysSet    = $rowOf("SELECT * FROM sys_settings ORDER BY id ASC LIMIT 1");
        $settings  = $rowOf("SELECT id, sign_up_points, sign_up_bonus_new_user, sign_up_bonus_existing_user, without_refferal_bonus_new_user FROM settings ORDER BY id ASC LIMIT 1");

        return $this->success_response("Rewards.", [
            'kpi' => [
                'claims_today'  => $claimsToday,
                'litties_today' => $littiesToday,
                'claim_rate'    => $claimRate,
                'avg_streak'    => round($avgStreak, 1),
            ],
            'daily_bonus' => $daily,      // first_day..seventh_day
            'sys_settings'=> $sysSet,     // refferal_bonus, with_rrefferal_bonus, sign_in_bonus
            'settings'    => $settings,   // sign_up_points, sign_up_bonus_new_user, etc.
        ]);
    }

    public function saveDaily()
    {
        $raw  = file_get_contents("php://input");
        $data = json_decode($raw, true) ?: [];
        $db = Database::connect();

        $payload = [];
        foreach (['first_day','second_day','third_day','fourth_day','fifth_day','sixth_day','seventh_day'] as $k) {
            if (isset($data[$k])) $payload[$k] = (int) $data[$k];
        }
        if (!$payload) return $this->error_response("No day values provided.");

        $existing = $db->table('sys_daily_bonus')->orderBy('id', 'ASC')->get()->getRowArray();
        if ($existing) {
            $db->table('sys_daily_bonus')->where('id', $existing['id'])->update($payload);
        } else {
            $db->table('sys_daily_bonus')->insert($payload);
        }
        return $this->success_response("Daily bonus updated.", $payload);
    }

    public function saveSignup()
    {
        $raw  = file_get_contents("php://input");
        $data = json_decode($raw, true) ?: [];
        $db = Database::connect();

        // settings table
        $sp = [];
        foreach (['sign_up_points','sign_up_bonus_new_user','sign_up_bonus_existing_user','without_refferal_bonus_new_user'] as $k) {
            if (isset($data[$k])) $sp[$k] = (int) $data[$k];
        }
        if ($sp) {
            $existing = $db->table('settings')->orderBy('id', 'ASC')->get()->getRowArray();
            if ($existing) $db->table('settings')->where('id', $existing['id'])->update($sp);
            else           $db->table('settings')->insert($sp);
        }

        // sys_settings: sign_in_bonus
        if (isset($data['sign_in_bonus'])) {
            $existing = $db->table('sys_settings')->orderBy('id','ASC')->get()->getRowArray();
            $pl = ['sign_in_bonus' => (int) $data['sign_in_bonus']];
            if ($existing) $db->table('sys_settings')->where('id', $existing['id'])->update($pl);
            else           $db->table('sys_settings')->insert($pl);
        }
        return $this->success_response("Signup rewards updated.", array_merge($sp, isset($data['sign_in_bonus']) ? ['sign_in_bonus'=>(int)$data['sign_in_bonus']] : []));
    }

    public function saveReferral()
    {
        $raw  = file_get_contents("php://input");
        $data = json_decode($raw, true) ?: [];
        $db = Database::connect();

        $pl = [];
        foreach (['refferal_bonus','with_rrefferal_bonus'] as $k) {
            if (isset($data[$k])) $pl[$k] = (int) $data[$k];
        }
        if (!$pl) return $this->error_response("No referral values provided.");

        $existing = $db->table('sys_settings')->orderBy('id','ASC')->get()->getRowArray();
        if ($existing) $db->table('sys_settings')->where('id', $existing['id'])->update($pl);
        else           $db->table('sys_settings')->insert($pl);
        return $this->success_response("Referral rewards updated.", $pl);
    }
}
