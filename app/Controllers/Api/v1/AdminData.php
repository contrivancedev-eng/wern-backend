<?php

namespace App\Controllers\Api\v1;

use App\Controllers\Api\ApiController;
use Config\Database;

class AdminData extends ApiController
{
    public function users_list()
    {
        $db = Database::connect();

        $sql = "
            SELECT u.id, u.full_name, u.nickname, u.country_code, u.phone_number,
                   u.email, u.membership_card_number, u.card_points, u.referal_code,
                   u.user_image, u.status, u.create_on,
                   COALESCE((
                       SELECT SUM(CASE WHEN type=1 THEN points ELSE -points END)
                       FROM earn_litties el WHERE el.user_id = u.id
                   ), 0) AS points_balance,
                   COALESCE((
                       SELECT SUM(points) FROM earn_litties el
                       WHERE el.user_id = u.id AND el.type = 1
                   ), 0) AS points_earned,
                   COALESCE((
                       SELECT SUM(steps) FROM user_step_events se WHERE se.user_id = u.id
                   ), 0) AS total_steps,
                   (SELECT COUNT(*) FROM users r WHERE r.referral_user_id = u.id) AS referrals_count
            FROM users u
            ORDER BY u.id DESC
        ";
        $rows = $db->query($sql)->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id'                     => $r['id'],
                'full_name'              => $r['full_name'],
                'nickname'               => $r['nickname'],
                'country_code'           => $r['country_code'],
                'phone_number'           => $r['phone_number'],
                'email'                  => $r['email'],
                'membership_card_number' => $r['membership_card_number'],
                'card_points'            => $r['card_points'],
                'points_balance'         => (int) $r['points_balance'],
                'points_earned'          => (int) $r['points_earned'],
                'total_steps'            => (int) $r['total_steps'],
                'referrals_count'        => (int) $r['referrals_count'],
                'referal_code'           => $r['referal_code'],
                'user_image'             => $r['user_image'],
                'status'                 => $r['status'],
                'create_on'              => $r['create_on'],
            ];
        }
        return $this->success_response("Users fetched.", $out);
    }
}
