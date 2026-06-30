<?php
namespace App\Controllers\Api\v1;
use App\Controllers\Api\ApiController;
use App\Models\Operation;

class Points extends ApiController
{
    public function member_wise_point_transaction() {
        $operation = new Operation();
        $token = $this->input_get('token');
    
        if (!$token) {
            return $this->error_response("Token is required.");
        }
    
        try {
            $decoded = decode_jwt($token, jwt_secret());
            $user_id = $decoded->id ?? null;
    
            if (!$user_id) {
                return $this->error_response("Invalid token: User ID missing.");
            }
        } catch (Exception $e) {
            return $this->error_response("Invalid token: " . $e->getMessage());
        }
    
        $month = $this->input_get('month') ?: date('m');
        $year = $this->input_get('year') ?: date('Y');
    
        $start_date = "$year-$month-01 00:00:00";
        $end_date = date("Y-m-t 23:59:59", strtotime($start_date));
    
        $allTransactionsCondition = "user_id = '$user_id'";
        $allTransactions = $operation->get_data("points", "*", [], "create_at", "DESC", "", "0", [], [], $allTransactionsCondition);
    
        $currentMonthCondition = "user_id = '$user_id' AND create_at >= '$start_date' AND create_at <= '$end_date'";
        $currentMonthTransactions = $operation->get_data("points", "*", [], "create_at", "DESC", "", "0", [], [], $currentMonthCondition);
    
        $total_credit = 0.0;
        $total_debit = 0.0;
    
        if ($allTransactions['num_rows'] > 0) {
            foreach ($allTransactions['result'] as $row) {
                if ($row->type == 1) {
                    $total_credit += (float) $row->points;
                } elseif ($row->type == 2) {
                    $total_debit += (float) $row->points;
                }
            }
        }
    
        $total_points = $total_credit - $total_debit;
    
        $current_month_credit = 0.0;
        $current_month_debit = 0.0;
    
        if ($currentMonthTransactions['num_rows'] > 0) {
            foreach ($currentMonthTransactions['result'] as $row) {
                if ($row->type == 1) {
                    $current_month_credit += (float) $row->points;
                } elseif ($row->type == 2) {
                    $current_month_debit += (float) $row->points;
                }
            }
        }
    
        $current_month_points = $current_month_credit - $current_month_debit;
    
        return $this->success_response("Point transactions fetched successfully.", [
            "total_credit" => $total_credit,
            "total_debit" => $total_debit,
            "total_points" => $total_points,
            "current_month_credit" => $current_month_credit,
            "current_month_debit" => $current_month_debit,
            "current_month_points" => $current_month_points,
            "transactions" => $allTransactions['result']
        ]);
    }
    
    
    
    
    

    // public function generate_refferal_code() {
    //     $operation = new Operation();
    //     $token = $this->input_get('token');
    
    //     if (!$token) {
    //         return $this->error_response("Token is required.");
    //     }
    
    //     try {
    //         $decoded = decode_jwt($token, jwt_secret());
    //         $user_id = $decoded->id ?? null;
    
    //         if (!$user_id) {
    //             return $this->error_response("Invalid token: User ID missing.");
    //         }
    //     } catch (Exception $e) {
    //         return $this->error_response("Invalid token: " . $e->getMessage());
    //     }
    
    //     $lastValidCode = $operation->get_data("refferal_code", "valid_date", ['user_id' => $user_id], "id", "DESC", 1);
    //     if ($lastValidCode['num_rows'] > 0) {
    //         $validUntil = $lastValidCode['result'][0]->valid_date;
    //         if (strtotime(date('Y-m-d')) < strtotime($validUntil)) {
    //             return $this->success_response("Referral codes already generated for this month.");
    //         }
    //     }
    
    //     $userInfo = $operation->get_data("users", "*", ['id' => $user_id]);
    //     if ($userInfo['num_rows'] == 0) {
    //         return $this->error_response("User not found.");
    //     }
    
    //     $fullName = $userInfo['result'][0]->full_name ?? '';
    //     $namePart = strtoupper(substr(str_replace(' ', '', $fullName), 0, 4));
    //     $prefix = "LBT-$namePart";
    
    //     $setting = $operation->get_data("settings", "*", [], "id", "DESC", 1);
    //     $bonusPoint = $setting['result'][0]->sign_up_bonus_new_user ?? 0;
    //     $existingbonusPoint = $setting['result'][0]->sign_up_bonus_existing_user ?? 0;
    
    //     $lastCodeData = $operation->get_data("refferal_code", "refferal_code", ['user_id' => $user_id], "id", "DESC", 1);
    //     $lastNumber = 0;
    //     if ($lastCodeData['num_rows'] > 0) {
    //         $lastCode = $lastCodeData['result'][0]->refferal_code;
    //         $parts = explode('-', $lastCode);
    //         $lastNumber = isset($parts[2]) ? intval($parts[2]) : 0;
    //     }
    
    //     for ($i = 1; $i <= 10; $i++) {
    //         $codeNumber = str_pad($lastNumber + $i, 4, '0', STR_PAD_LEFT);
    //         $referralCode = "$prefix-$codeNumber";
    
    //         $insertData = [
    //             'user_id'       => $user_id,
    //             'refferal_code' => $referralCode,
    //             'point_award'   => $bonusPoint,
    //             'existing_point_award'   => $existingbonusPoint,
    //             'create_date'   => date('Y-m-d H:i:s'),
    //             'valid_date'    => date('Y-m-d H:i:s', strtotime("+1 month")),
    //             'status'        => 0
    //         ];
    
    //         $operation->insert_data('refferal_code', $insertData);
    //     }

    //     if($user_id){
    //         $year = !empty($input_year) ? $input_year : date('Y');
    //         $month = !empty($input_month) ? $input_month : date('m');
        
    //         $rawCondition = "user_id = '$user_id' AND (
    //             (MONTH(create_date) = '{$month}' AND YEAR(create_date) = '{$year}')
    //             OR
    //             (MONTH(valid_date) = '{$month}' AND YEAR(valid_date) = '{$year}')
    //         )";


    //         $referrals = $operation->get_data("refferal_code", "*", [], "id", "DESC", "", "0", [], [], $rawCondition);
    
    //         if ($referrals['num_rows'] == 0) {
    //             return $this->error_response("No referral codes found for this month.");
    //         }

    //     }
        

    
    //     return $this->success_response("Referral codes generated successfully.", $referrals['result']);
    // }

    public function generate_refferal_code() {
        $operation = new Operation();
        $token = $this->input_get('token');
    
        if (!$token) {
            return $this->error_response("Token is required.");
        }
    
        try {
            $decoded = decode_jwt($token, jwt_secret());
            $user_id = $decoded->id ?? null;
    
            if (!$user_id) {
                return $this->error_response("Invalid token: User ID missing.");
            }
        } catch (Exception $e) {
            return $this->error_response("Invalid token: " . $e->getMessage());
        }
    
        $lastValidCode = $operation->get_data("refferal_code", "valid_date", ['user_id' => $user_id], "id", "DESC", 1);
        if ($lastValidCode['num_rows'] > 0) {
            $validUntil = $lastValidCode['result'][0]->valid_date;
            if (strtotime(date('Y-m-d')) < strtotime($validUntil)) {
                return $this->success_response("Referral codes already generated for this month.");
            }
        }
    
        $userInfo = $operation->get_data("users", "*", ['id' => $user_id]);
        if ($userInfo['num_rows'] == 0) {
            return $this->error_response("User not found.");
        }
    
        $fullName = $userInfo['result'][0]->full_name ?? '';
        $namePart = strtoupper(substr(str_replace(' ', '', $fullName), 0, 3));
        $prefix = "LBT-$namePart$user_id";
    
        $setting = $operation->get_data("settings", "*", [], "id", "DESC", 1);
        $bonusPoint = $setting['result'][0]->sign_up_bonus_new_user ?? 0;
        $existingBonusPoint = $setting['result'][0]->sign_up_bonus_existing_user ?? 0;
    
        $lastCodeData = $operation->get_data("refferal_code", "refferal_code", ['user_id' => $user_id], "id", "DESC", 1);
        $lastNumber = 0;
        if ($lastCodeData['num_rows'] > 0) {
            $lastCode = $lastCodeData['result'][0]->refferal_code;
            $parts = explode('-', $lastCode);
            $lastNumber = isset($parts[2]) ? intval($parts[2]) : 0;
        }
    
        for ($i = 1; $i <= 10; $i++) {
            $codeNumber = str_pad($lastNumber + $i, 4, '0', STR_PAD_LEFT);
            $referralCode = "$prefix-$codeNumber";
    
            $insertData = [
                'user_id'              => $user_id,
                'refferal_code'        => $referralCode,
                'point_award'          => $bonusPoint,
                'existing_point_award' => $existingBonusPoint,
                'create_date'          => date('Y-m-d H:i:s'),
                'valid_date'           => date('Y-m-d H:i:s', strtotime("+1 month")),
                'status'               => 0
            ];
    
            $operation->insert_data('refferal_code', $insertData);
        }
    
        $year = date('Y');
        $month = date('m');
    
        $rawCondition = "user_id = '$user_id' AND (
            (MONTH(create_date) = '{$month}' AND YEAR(create_date) = '{$year}')
            OR
            (MONTH(valid_date) = '{$month}' AND YEAR(valid_date) = '{$year}')
        )";
    
        $referrals = $operation->get_data("refferal_code", "*", [], "id", "DESC", "", "0", [], [], $rawCondition);
    
        if ($referrals['num_rows'] == 0) {
            return $this->error_response("No referral codes found for this month.");
        }
    
        return $this->success_response("Referral codes generated successfully.", $referrals['result']);
    }
    
    
    


    

    
    
}
?>