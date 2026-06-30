<?php
namespace App\Controllers\Api\v1;
use App\Controllers\Api\ApiController;
use App\Models\Operation;

class Club extends ApiController
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

    public function refferal_code_list_with_status() {
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
    
        $input_year = $this->input_get('year');
        $input_month = $this->input_get('month');
    
        $year = !empty($input_year) ? $input_year : date('Y');
        $month = !empty($input_month) ? $input_month : date('m');
    
        $rawCondition = "user_id = '$user_id' AND (
            (MONTH(create_date) = '{$month}' AND YEAR(create_date) = '{$year}')
            OR
            (MONTH(valid_date) = '{$month}' AND YEAR(valid_date) = '{$year}')
        )";
    
        $referrals = $operation->get_data("refferal_code", "*", [], "id", "DESC", "", "0", [], [], $rawCondition);
    
        if ($referrals['num_rows'] == 0) {
            return $this->error_response("No referral codes found for this month.");
        }
    
        return $this->success_response("Referral codes fetched successfully.", $referrals['result']);
    }

    public function winners_club_users() {
        $operation = new Operation();
    
        // Get token
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
    
        // Get optional search year/month
        $input_year = $this->input_get('year');
        $input_month = $this->input_get('month');
    
        // Default year/month current
        $year = !empty($input_year) ? $input_year : date('Y');
        $month = !empty($input_month) ? $input_month : date('m');
    
        // Build raw condition for current year/month
        $rawCondition = "(MONTH(lottery_winers.create_on) = '{$month}' AND YEAR(lottery_winers.create_on) = '{$year}')";
    
        // Get top 3 winners
        $winners = $operation->get_data(
            "lottery_winers", 
            "id, lottery_id, user_id, points, create_on", 
            [], 
            "points", // Order by points to get top winners
            "DESC", 
            "3", // Limit to top 3 winners
            "0", 
            [], 
            [], 
            $rawCondition
        );
    
        // Check if winners found
        if ($winners['num_rows'] == 0) {
            return $this->error_response("No winners found for this month.");
        }
    
        // Get user names for the top 3 winners
        $top_winners = $winners['result'];
    
        foreach ($top_winners as &$winner) {
            // Get user details based on user_id (not joined in the first query)
            $user = $operation->get_data("users", "full_name", ["id" => $winner->user_id]);
    
            // Check if user found and append fullname
            if ($user['num_rows'] > 0) {
                $winner->fullname = $user['result'][0]->full_name;
            } else {
                $winner->fullname = "Unknown User"; // If no user found
            }
        }
    
        return $this->success_response("Top 3 Winners fetched successfully.", $top_winners);
    }

    public function get_latest_club() {
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
    
        $current_month = date('m');
        $current_year = date('Y');

        //$rawCondition = "(MONTH(lottery_date) = '{$current_month}' AND status = 1";
        $rawCondition = "(MONTH(lottery_date) = '{$current_month}' AND YEAR(lottery_date) = '{$current_year}') AND status = 1";
        //$rawCondition = "lottery_date > '{$today}' AND status = 1";

        // print_r($rawCondition);
        // die();
        $club = $operation->get_data("monthly_lottery", "*", [], "lottery_date", "ASC", "1", "0", [], [], $rawCondition);
    
        if ($club['num_rows'] == 0) {
            return $this->error_response("No upcoming clubs found.");
        }
    
        $latest_club = $club['result'][0];
        $lottery_date = $club['result'][0]->lottery_date;
        
        $clubuser = $operation->get_data("join_lottery_user", "*", ['user_id' => $user_id, 'lottery_id' => $latest_club->id]);


    
        if ($clubuser['num_rows'] == 0) {
            return $this->success_response("Upcoming club available.", $latest_club);
        } 
    
        return $this->error_response("You have already joined this club.", ['lottery_winning_date'=>$lottery_date]);
    }

    public function join_club_user() {
        $operation = new Operation();
        $rawData = file_get_contents("php://input");
        $decodedData = json_decode($rawData, true);
    
        $token = $decodedData['token'] ?? '';
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
    
        
        $current_month = date('m');
        $current_year = date('Y');
        
        $rawCondition = "(MONTH(lottery_date) = '{$current_month}' AND YEAR(lottery_date) = '{$current_year}') AND status = 1";
        $club = $operation->get_data("monthly_lottery", "*", [], "lottery_date", "ASC", "1", "0", [], [], $rawCondition);
    
        if ($club['num_rows'] == 0) {
            return $this->error_response("No upcoming clubs found.");
        }
    
        $latest_club = $club['result'][0];
        $lottery_winning_date = $latest_club->lottery_date;
    
        // First check if user already joined
        $clubuser = $operation->get_data("join_lottery_user", "*", ['user_id' => $user_id, 'lottery_id' => $latest_club->id]);
        if ($clubuser['num_rows'] > 0) {
            return $this->error_response("You have already joined this club.");
        }
    
        $year = date('Y');
        $month = date('m');
    
        $credit_raw_condition = "user_id = '{$user_id}' AND type = 1 AND YEAR(create_at) = '{$year}' AND MONTH(create_at) = '{$month}'";
        $debit_raw_condition = "user_id = '{$user_id}' AND type = 2 AND YEAR(create_at) = '{$year}' AND MONTH(create_at) = '{$month}'";
    
        $credit_points_data = $operation->get_data("points", "points", [], "id", "DESC", "", "0", [], [], $credit_raw_condition);
        $debit_points_data = $operation->get_data("points", "points", [], "id", "DESC", "", "0", [], [], $debit_raw_condition);
    
        $total_credit = 0;
        $total_debit = 0;
    
        if ($credit_points_data['num_rows'] > 0) {
            foreach ($credit_points_data['result'] as $credit) {
                $total_credit += $credit->points;
            }
        }
        if ($debit_points_data['num_rows'] > 0) {
            foreach ($debit_points_data['result'] as $debit) {
                $total_debit += $debit->points;
            }
        }
    
        $available_points = $total_credit - $total_debit;
    
        if ($available_points < $latest_club->joining_points) {
            return $this->error_response("Not enough points to join the club.");
        }
    
        $points_to_deduct = $latest_club->joining_points;
    
        $points_data = [
            "user_id" => $user_id,
            "points" => $points_to_deduct,
            "type" => 2,
            "description" => "You've spent {$points_to_deduct} points for joining Club.",
            "create_at" => date("Y-m-d H:i:s")
        ];
    
        $points_id = $operation->insert_data("points", $points_data);
    
        $join_user_data = [
            "user_id" => $user_id,
            "lottery_id" => $latest_club->id,
            "create_on" => date("Y-m-d H:i:s")
        ];
    
        $operation->insert_data("join_lottery_user", $join_user_data);
        $join_user_data =  ['lottery_winning_date' => $lottery_winning_date];
    
        return $this->success_response("User has successfully joined the club.", $join_user_data);
    }




    //ADMIN API

    public function get_all_club() {
        $operation = new Operation();
        $year = $this->input_get('year');
        $month = $this->input_get('month');
    
        if (!$year || !$month) {
            return $this->error_response("Year and Month are required!");
        }
    
        $rawCondition = "(MONTH(lottery_date) = '{$month}' AND YEAR(lottery_date) = '{$year}')";
        $club = $operation->get_data("monthly_lottery", "*", [], "lottery_date", "ASC", "1", "0", [], [], $rawCondition);
    
        if ($club['num_rows'] == 0) {
            return $this->error_response("No clubs found.");
        }
    
        $latest_club = $club['result'][0];
        $club_id = isset($latest_club->id) ? $latest_club->id : null;
    
        if (!$club_id) {
            return $this->error_response("Invalid club data.");
        }
    
        // Use get_data to get users (by lottery_id)
        $userCondition = []; // Leave this empty since full logic goes into $rawCondition
        $userFields = "u.full_name, u.user_image";
        $rawCondition = "u.id = j.user_id AND j.lottery_id = '{$club_id}'";
        $users = $operation->get_data("users u, join_lottery_user j", $userFields, $userCondition, "j.id", "ASC", "", "0", [], [], $rawCondition);

        $userList = $users['num_rows'] > 0 ? $users['result'] : [];


        $lottery_winerss = $operation->get_data("users u, lottery_winers j", $userFields, $userCondition, "j.id", "ASC", "", "0", [], [], $rawCondition);
        // $lottery_winerss = $operation->get_data("lottery_winers", "*", ["lottery_id"=>$club_id]);
        $lottery_winersList = $lottery_winerss['num_rows'] > 0 ? $lottery_winerss['result'] : [];

        // Prepare final response
        $response = [
            'id' => $latest_club->id,
            'title' => $latest_club->title,
            'description' => $latest_club->description,
            'lottery_date' => $latest_club->lottery_date,
            'joining_date' => $latest_club->joining_date,
            'joining_points' => $latest_club->joining_points,
            'create_on' => $latest_club->create_on,
            'update_on' => $latest_club->update_on,
            'status' => $latest_club->status,
            'users' => $userList,
            'winers' => $lottery_winersList
        ];
    
        return $this->success_response("Club available.", $response);
    }
    
    
    
    
    
    


    
    
    
    
    
    
    
    
    
    

    
    
    

}
?>
