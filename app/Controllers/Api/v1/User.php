<?php
namespace App\Controllers\Api\v1;
use App\Controllers\Api\ApiController;
use App\Models\Operation;

class User extends ApiController
{

    function decode_json($rawData) {
        $decodedData = json_decode($rawData, true);
    
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Invalid JSON data: ' . json_last_error_msg()
            ];
        }
    
        return [
            'success' => true,
            'data' => $decodedData
        ];
    }

    public function get_user_details() {
        $operation = new Operation();

        $token = $this->input_get('token');
        if (!$token) {
            return $this->error_response("Token is required.");
        }

        // Get user_id using Operation Model
        $jwtData = $operation->get_user_id_from_token($token);
        if (!$jwtData['status']) {
            return $this->error_response($jwtData['error']);
        }

        $user_id = $jwtData['user_id'];

        /* ================= USER DETAILS ================= */

        $selectFields = "id, full_name, nickname, country_code, phone_number, email, membership_card_number, card_points, referral_user_id, referal_code, user_image, status, create_on";

        $user = $operation->get_data("users", $selectFields, ["id" => $user_id]);

        if ($user['num_rows'] == 0) {
            return $this->error_response("User not found.");
        }

        /* ================= TOTAL EARN POINTS ================= */

        $earnData = $operation->get_data("earn_litties", "points", ["user_id" => $user_id]);

        $totalEarnPoints = 0;
        if ($earnData['num_rows'] > 0) {
            foreach ($earnData['result'] as $row) {
                $totalEarnPoints += (float)$row->points;
            }
        }

        /* ================= TIER CALCULATION (RANGE BASED) ================= */

        $tiers = $operation->get_data("user_tier", "id,name,points", ["status" => 1], "points", "ASC");

        $currentTier = null;
        $nextTier = null;

        if ($tiers['num_rows'] > 0) {
            foreach ($tiers['result'] as $index => $tier) {
                if ($totalEarnPoints <= $tier->points) {
                    $currentTier = $tier;
                    $nextTier = $tiers['result'][$index + 1] ?? null;
                    break;
                }
            }

            // If points exceed highest tier
            if (!$currentTier) {
                $currentTier = end($tiers['result']);
                $nextTier = null;
            }
        }

        $pointsNeeded = $nextTier ? max(0, ($currentTier->points + 1) - $totalEarnPoints) : 0;

        /* ================= FINAL RESPONSE ================= */

        $response = $user['result'][0];
        $response->tier_details = [
            "total_earn_points" => round($totalEarnPoints, 2),
            "current_tier" => $currentTier ? $currentTier->name : null,
            "current_tier_max_points" => $currentTier ? (int)$currentTier->points : 0,
            "next_tier" => $nextTier ? $nextTier->name : null,
            "next_tier_max_points" => $nextTier ? (int)$nextTier->points : 0,
            "points_needed_for_next_tier" => $pointsNeeded
        ];

        return $this->success_response("User details fetched successfully.", $response);
    }

    //========CLAIM==========
    public function daily_claim(){
        $operation = new Operation();

        $token = $this->input_get('token');
        if (!$token) {
            return $this->error_response("Token is required.");
        }

        // Validate user
        $jwtData = $operation->get_user_id_from_token($token);
        if (!$jwtData['status']) {
            return $this->error_response($jwtData['error']);
        }

        $user_id = $jwtData['user_id'];
        $today = date("Y-m-d");

        // Fetch daily bonus structure
        $bonus = $operation->get_data("sys_daily_bonus", "*", ["id" => 1]);
        if ($bonus['num_rows'] == 0) {
            return $this->error_response("Daily bonus structure missing.");
        }

        $bonusData = $bonus['result'][0];

        // Get user's last claim
        $claim = $operation->get_data("user_daily_claim_status", "*", ["user_id" => $user_id]);

        $nextDay = 1;

        if ($claim['num_rows'] > 0) {

            $lastClaim = $claim['result'][0];
            $lastDate = $lastClaim->last_claim_date;
            $lastDay  = $lastClaim->claim_day;

            // Check if user already claimed today
            if ($lastDate == $today) {
                return $this->error_response("Already claimed for today.");
            }

            // Check if yesterday claimed → continue
            if ($lastDate == date("Y-m-d", strtotime("-1 day"))) {
                $nextDay = $lastDay + 1;
                if ($nextDay > 7) $nextDay = 1;  // Restart after day 7
            } else {
                $nextDay = 1; // missed → reset to Day 1
            }
        }

        // Get the points for that day
        $fieldName = [
            1 => "first_day",
            2 => "second_day",
            3 => "third_day",
            4 => "fourth_day",
            5 => "fifth_day",
            6 => "sixth_day",
            7 => "seventh_day",
        ];

        $points = $bonusData->{$fieldName[$nextDay]};

        // Insert into earn_litties
        $insertData = [
            "user_id"          => $user_id,
            "type"             => 1,
            "earn_category_id" => 2, // Claim
            "points"           => $points,
            "description"      => "Daily Claim - Day ".$nextDay,
            "date"             => date("Y-m-d H:i:s")
        ];

        $operation->insert_data("earn_litties", $insertData);

        // Update claim status table
        $statusData = [
            "last_claim_date" => $today,
            "claim_day"       => $nextDay
        ];

        if ($claim['num_rows'] == 0) {
            $statusData["user_id"] = $user_id;
            $operation->insert_data("user_daily_claim_status", $statusData);
        } else {
            $operation->update_data("user_daily_claim_status", ["user_id" => $user_id], $statusData);
        }

        // Return success
        return $this->success_response("Claim successful.", [
            "day"        => $nextDay,
            "points"     => $points,
            "message"    => "You earned $points points on Day $nextDay!"
        ]);
    }

    public function get_daily_claim_status(){
        $operation = new Operation();

        $token = $this->input_get('token');
        if (!$token) {
            return $this->error_response("Token is required.");
        }

        // Validate user
        $jwtData = $operation->get_user_id_from_token($token);
        if (!$jwtData['status']) {
            return $this->error_response($jwtData['error']);
        }

        $user_id = $jwtData['user_id'];
        $today = date("Y-m-d");

        // Fetch daily bonus list
        $bonus = $operation->get_data("sys_daily_bonus", "*", ["id" => 1]);
        if ($bonus['num_rows'] == 0) {
            return $this->error_response("Daily bonus data missing.");
        }
        $bonusData = $bonus['result'][0];

        // Get user last claim
        $claim = $operation->get_data("user_daily_claim_status", "*", ["user_id" => $user_id]);

        $status = [
            "today_claimed" => false,
            "current_day"   => 1,
            "next_points"   => $bonusData->first_day,
        ];

        if ($claim['num_rows'] > 0) {
            $claimRow = $claim['result'][0];
            $lastDate = $claimRow->last_claim_date;
            $lastDay  = $claimRow->claim_day;

            // Claimed today
            if ($lastDate == $today) {
                $status["today_claimed"] = true;
                $status["current_day"]   = $lastDay;

                // If user already claimed today → next day calculation (but no claim allowed)
                $nextDay = $lastDay + 1;
                if ($nextDay > 7) $nextDay = 1;

                $fieldMap = [
                    1 => "first_day",
                    2 => "second_day",
                    3 => "third_day",
                    4 => "fourth_day",
                    5 => "fifth_day",
                    6 => "sixth_day",
                    7 => "seventh_day"
                ];

                $status["next_points"] = $bonusData->{$fieldMap[$nextDay]};
            }
            else {
                // Not claimed today → determine which day to show next
                if ($lastDate == date("Y-m-d", strtotime("-1 day"))) {
                    $nextDay = $lastDay + 1;
                    if ($nextDay > 7) $nextDay = 1;
                } else {
                    $nextDay = 1; // missed → reset
                }

                $status["current_day"] = $nextDay;
                $fieldMap = [
                    1 => "first_day",
                    2 => "second_day",
                    3 => "third_day",
                    4 => "fourth_day",
                    5 => "fifth_day",
                    6 => "sixth_day",
                    7 => "seventh_day"
                ];
                $status["next_points"] = $bonusData->{$fieldMap[$nextDay]};
            }
        }

        // Time left for reset
        $midnight = strtotime('tomorrow');
        $now = time();
        $secondsLeft = $midnight - $now;

        $status["time_left"] = gmdate("H:i:s", $secondsLeft);

        // Prepare daily list for UI
        $status["daily_list"] = [
            ["day" => 1, "points" => $bonusData->first_day],
            ["day" => 2, "points" => $bonusData->second_day],
            ["day" => 3, "points" => $bonusData->third_day],
            ["day" => 4, "points" => $bonusData->fourth_day],
            ["day" => 5, "points" => $bonusData->fifth_day],
            ["day" => 6, "points" => $bonusData->sixth_day],
            ["day" => 7, "points" => $bonusData->seventh_day],
        ];

        return $this->success_response("Daily claim status fetched.", $status);
    }

    public function get_points_summary(){
        $op = new Operation();

        $token = $this->input_get('token');
        if (!$token) return $this->error_response("Token is required.");

        $jwt = $op->get_user_id_from_token($token);
        if (!$jwt['status']) return $this->error_response($jwt['error']);

        $user_id = $jwt['user_id'];
        $category_id = $this->input_get('category_id');

        // Build filter
        $condition = ["user_id" => $user_id];
        if (!empty($category_id)) $condition["earn_category_id"] = $category_id;

        // Get transactions
        $trx = $op->get_data("earn_litties", "*", $condition, "id", "DESC");

        $totalEarn = $totalDeduct = 0;
        $final = [];

        if ($trx['num_rows'] > 0) {
            foreach ($trx['result'] as $t) {

                ($t->type == 1) ? $totalEarn += $t->points : $totalDeduct += $t->points;

                // Get category name
                $cat = $op->get_data("earn_category", "category_name", ["id" => $t->earn_category_id]);
                $category_name = ($cat['num_rows'] > 0) ? $cat['result'][0]->category_name : "";

                // Final list
                $final[] = [
                    "id" => $t->id,
                    "type" => $t->type,
                    "earn_category_id" => $t->earn_category_id,
                    "category_name" => $category_name,
                    "points" => $t->points,
                    "description" => $t->description,
                    "date" => $t->date
                ];
            }
        }

        return $this->success_response("Points summary fetched.", [
            "total_earned"   => $totalEarn,
            "total_deducted" => $totalDeduct,
            "balance"        => $totalEarn - $totalDeduct,
            "transactions"   => $final
        ]);
    }

    public function get_refferal_users_history()
    {
        $operation = new Operation();

        $token = $this->input_get('token');
        if (!$token) {
            return $this->error_response("Token is required.");
        }

        // Validate user
        $jwtData = $operation->get_user_id_from_token($token);
        if (!$jwtData['status']) {
            return $this->error_response($jwtData['error']);
        }

        $user_id = (int)$jwtData['user_id'];

        /* ================= DIRECT REFERRALS ================= */

        $direct = $operation->get_data("users", "id,full_name,referral_user_id,referal_code,user_image,status", ["referral_user_id" => $user_id]);

        /* ================= NETWORK TREE ================= */

        $networkTree = $this->build_referral_tree($user_id, $operation, $totalCount);

        /* ================= RESPONSE ================= */

        $response = [
            "user_id" => $user_id,
            "direct_referrals" => $direct['result'] ?? [],
            "direct_count" => $direct['num_rows'],
            "total_network_count" => $totalCount,
            "network_tree" => $networkTree
        ];

        return $this->success_response("Referral history fetched successfully.", $response);
    }

    private function build_referral_tree($user_id, $operation, &$totalCount)
    {
        $children = $operation->get_data("users", "id,full_name,referral_user_id,referal_code,user_image,status", ["referral_user_id" => $user_id]);

        $tree = [];

        if ($children['num_rows'] > 0) {
            foreach ($children['result'] as $user) {

                $totalCount++;

                $tree[] = [
                    "id" => $user->id,
                    "full_name" => $user->full_name,
                    "referal_code" => $user->referal_code,
                    "user_image" => $user->user_image,
                    "status" => $user->status,
                    "children" => $this->build_referral_tree($user->id, $operation, $totalCount)
                ];
            }
        }

        return $tree;
    }

    public function user_activity(){
        $operation = new Operation();

        $token = $this->input_get('token');
        if (!$token) {
            return $this->error_response("Token is required.");
        }

        // Validate user
        $jwtData = $operation->get_user_id_from_token($token);
        if (!$jwtData['status']) {
            return $this->error_response($jwtData['error']);
        }

        $user_id = (int)$jwtData['user_id'];

        $activities = [];

        /* ================= STEP EVENTS ================= */

        $stepData = $operation->get_data("user_step_events", "*", [], "", "event_time DESC", "10", "0", [], [], "user_id = {$user_id}");

        if ($stepData['num_rows'] > 0) {
            foreach ($stepData['result'] as $row) {
                $activities[] = [
                    "type" => "step",
                    "steps" => (int)$row->steps,
                    "km" => (float)$row->kilometre,
                    "kcal" => (float)$row->kcal,
                    "date" => $row->event_time,
                    "raw" => $row
                ];
            }
        }

        /* ================= EARN LITTIES ================= */

        $earnData = $operation->get_data("earn_litties", "*", [], "", "date DESC", "10", "0", [], [], "user_id = {$user_id}");

        if ($earnData['num_rows'] > 0) {
            foreach ($earnData['result'] as $row) {
                $activities[] = [
                    "type" => "earn",
                    "points" => (float)$row->points,
                    "earn_category_id" => $row->earn_category_id,
                    "description" => $row->description,
                    "date" => $row->date,
                    "raw" => $row
                ];
            }
        }

        /* ================= DAILY CLAIM ================= */

        $claimData = $operation->get_data("user_daily_claim_status", "*", [], "", "last_claim_date DESC", "10", "0", [], [], "user_id = {$user_id}");

        if ($claimData['num_rows'] > 0) {
            foreach ($claimData['result'] as $row) {
                $activities[] = [
                    "type" => "daily_claim",
                    "claim_day" => $row->claim_day,
                    "date" => $row->last_claim_date,
                    "raw" => $row
                ];
            }
        }

        /* ================= USER GOALS ================= */

        $goalData = $operation->get_data("user_goals", "*", [], "", "updated_at DESC", "10", "0", [], [], "user_id = {$user_id}");

        if ($goalData['num_rows'] > 0) {
            foreach ($goalData['result'] as $row) {
                $activities[] = [
                    "type" => "goal",
                    "daily_step_goal" => $row->daily_step_goal,
                    "activity_level" => $row->activity_level,
                    "weekly_goal" => $row->weekly_goal,
                    "date" => $row->updated_at,
                    "raw" => $row
                ];
            }
        }

        /* ================= SORT & LIMIT ================= */

        usort($activities, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        $activities = array_slice($activities, 0, 10);

        /* ================= RESPONSE ================= */

        return $this->success_response(
            "User activity fetched successfully.",
            [
                "user_id" => $user_id,
                "latest_activity" => $activities
            ]
        );
    }

public function update_user_image() {

    $operation = new Operation();

    $token = $this->input_post('token');
    if (empty($token)) return $this->error_response("Token is required.");

    $jwtData = $operation->get_user_id_from_token($token);
    if (!$jwtData['status']) return $this->error_response($jwtData['error']);

    $user_id = (int)$jwtData['user_id'];

    $full_name    = $this->input_post('full_name');
    $phone_number = $this->input_post('phone_number');
    $nickname     = $this->input_post('nickname');

    $update_fields = [];

    if (!empty($_FILES['user_image']) && $_FILES['user_image']['size'] > 0) {
        $file = $_FILES['user_image'];
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return $this->error_response("Image upload failed.");
        }

        $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array(strtolower($ext), $allowedExt, true)) {
            return $this->error_response("Invalid image type. Allowed: jpg, jpeg, png, gif, webp.");
        }

        $file_name = uniqid('user_', true) . '.' . $ext;
        $uploadDir = rtrim(WEb_ASSIST_PHYSICAL_PATH, '/\\') . DIRECTORY_SEPARATOR . "user" . DIRECTORY_SEPARATOR;

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
            return $this->error_response("User upload folder not found and could not be created.");
        }

        if (!is_writable($uploadDir)) {
            return $this->error_response("User upload folder is not writable.");
        }

        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $file_name)) {
            return $this->error_response("Unable to save user image.");
        }

        $full_path = WEb_BASE_PATH . "public/user/" . $file_name;
        $update_fields['user_image'] = $full_path;
    }

    if (!empty($full_name)) $update_fields['full_name'] = $full_name;
    if (!empty($nickname))  $update_fields['nickname']  = $nickname;

    if (!empty($phone_number)) {
        // $exists = $operation->get_data(
        //     'users',
        //     '*',
        //     ['phone_number' => $phone_number, 'id !=' => $user_id]
        // );
        // if ($exists['num_rows'] > 0) {
        //     return $this->error_response("Phone number already in use by another user.");
        // }
        $update_fields['phone_number'] = $phone_number;
    }

    if (empty($update_fields)) {
        return $this->error_response("Nothing to update. Provide user_image, full_name, phone_number or nickname.");
    }

    $update = $operation->update_data(
        'users',
        ['id' => $user_id],
        $update_fields
    );

    return $update
        ? $this->success_response("User profile updated", $update_fields)
        : $this->error_response("Unable to update user profile");
}

public function get_weather_by_location() {

    $lat  = $this->input_get('latitude');
    $lon  = $this->input_get('longitude');
    $unit = strtoupper($this->input_get('unit')); // C or F

    if (empty($lat) || empty($lon)) {
        return $this->error_response("Latitude and longitude are required.");
    }

    // Default Celsius
    $temp_unit = "celsius";
    if ($unit === "F") {
        $temp_unit = "fahrenheit";
    }

    $url = "https://api.open-meteo.com/v1/forecast"
         . "?latitude={$lat}"
         . "&longitude={$lon}"
         . "&current_weather=true"
         . "&temperature_unit={$temp_unit}";

    $response = file_get_contents($url);
    $weatherData = json_decode($response, true);

    if (!isset($weatherData['current_weather'])) {
        return $this->error_response("Unable to fetch weather data.");
    }

    return $this->success_response("Weather fetched successfully", [
        "temperature" => $weatherData['current_weather']['temperature'],
        "unit"        => ($temp_unit === "fahrenheit") ? "°F" : "°C",
        "windspeed"   => $weatherData['current_weather']['windspeed'],
        "weathercode" => $weatherData['current_weather']['weathercode']
    ]);
}




    public function delete_all_data() {
        $operation = new Operation();

        $gmail  = $this->input_post('gmail');
        $reason = $this->input_post('reason');

        // Fallback: accept JSON body (application/json)
        if (empty($gmail) && empty($reason)) {
            $rawBody = file_get_contents('php://input');
            if (!empty($rawBody)) {
                $json = json_decode($rawBody, true);
                if (is_array($json)) {
                    $gmail  = trim($json['gmail']  ?? '');
                    $reason = trim($json['reason'] ?? '');
                }
            }
        }

        if (empty($gmail)) {
            return $this->error_response("Gmail is required.");
        }
        if (empty($reason)) {
            return $this->error_response("Reason is required.");
        }

        // Find user by email
        $user = $operation->get_data("users", "*", ["email" => $gmail]);

        if ($user['num_rows'] == 0) {
            return $this->error_response("No user found with this gmail.");
        }

        $user_id  = $user['result'][0]->id;
        $userData = $user['result'][0];

        // Collect all user data before deletion for json_log
        $json_log = ["user" => $userData];

        $earnData = $operation->get_data("earn_litties", "*", ["user_id" => $user_id]);
        $json_log['earn_litties'] = $earnData['result'];

        $stepData = $operation->get_data("user_step_events", "*", ["user_id" => $user_id]);
        $json_log['user_step_events'] = $stepData['result'];

        $claimData = $operation->get_data("user_daily_claim_status", "*", ["user_id" => $user_id]);
        $json_log['user_daily_claim_status'] = $claimData['result'];

        $goalData = $operation->get_data("user_goals", "*", ["user_id" => $user_id]);
        $json_log['user_goals'] = $goalData['result'];

        $lotteryJoin = $operation->get_data("join_lottery_user", "*", ["user_id" => $user_id]);
        $json_log['join_lottery_user'] = $lotteryJoin['result'];

        $lotteryWin = $operation->get_data("lottery_winers", "*", ["user_id" => $user_id]);
        $json_log['lottery_winers'] = $lotteryWin['result'];

        $refCode = $operation->get_data("refferal_code", "*", ["user_id" => $user_id]);
        $json_log['refferal_code'] = $refCode['result'];

        $pointsData = $operation->get_data("points", "*", ["user_id" => $user_id]);
        $json_log['points'] = $pointsData['result'];

        $notifData = $operation->get_data("user_notifications", "*", ["user_id" => $user_id]);
        $json_log['user_notifications'] = $notifData['result'];

        $notifSettings = $operation->get_data("user_notification_settings", "*", ["user_id" => $user_id]);
        $json_log['user_notification_settings'] = $notifSettings['result'];

        $reviewData = $operation->get_data("user_reviews", "*", ["user_id" => $user_id]);
        $json_log['user_reviews'] = $reviewData['result'];

        // Insert into delete_log before deleting
        $operation->insert_data("delete_log", [
            "gmail"    => $gmail,
            "reason"   => $reason,
            "json_log" => json_encode($json_log)
        ]);

        // Delete all related data from all tables
        $deleted = [];
        $deleted['earn_litties']            = $operation->delete_data("earn_litties", ["user_id" => $user_id]);
        $deleted['user_step_events']        = $operation->delete_data("user_step_events", ["user_id" => $user_id]);
        $deleted['user_daily_claim_status'] = $operation->delete_data("user_daily_claim_status", ["user_id" => $user_id]);
        $deleted['user_goals']              = $operation->delete_data("user_goals", ["user_id" => $user_id]);
        $deleted['join_lottery_user']       = $operation->delete_data("join_lottery_user", ["user_id" => $user_id]);
        $deleted['lottery_winers']          = $operation->delete_data("lottery_winers", ["user_id" => $user_id]);
        $deleted['refferal_code']           = $operation->delete_data("refferal_code", ["user_id" => $user_id]);
        $deleted['points']                  = $operation->delete_data("points", ["user_id" => $user_id]);
        $deleted['user_notifications']         = $operation->delete_data("user_notifications", ["user_id" => $user_id]);
        $deleted['user_notification_settings'] = $operation->delete_data("user_notification_settings", ["user_id" => $user_id]);
        $deleted['user_reviews']               = $operation->delete_data("user_reviews", ["user_id" => $user_id]);

        // Finally delete the user record
        $deleted['users'] = $operation->delete_data("users", ["id" => $user_id]);

        return $this->success_response("All data deleted successfully for: " . $gmail, [
            "gmail"           => $gmail,
            "user_id"         => $user_id,
            "deleted_records" => $deleted
        ]);
    }

}
?>
