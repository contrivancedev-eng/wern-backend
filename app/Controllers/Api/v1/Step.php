<?php
namespace App\Controllers\Api\v1;
use App\Controllers\Api\ApiController;
use App\Controllers\Api\v1\Notification;
use App\Models\Operation;

class Step extends ApiController
{
    public function get_step_summary_monthly()
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

        // Optional month/year (default current)
        $month = $this->input_get('month') ?: date('m');
        $year  = $this->input_get('year')  ?: date('Y');

        $startDate = "{$year}-{$month}-01";
        $endDate   = date("Y-m-t", strtotime($startDate));

        /* ================= RAW CONDITION ================= */

        $rawCondition = "user_id = {$user_id} 
                        AND DATE(event_time) BETWEEN '{$startDate}' AND '{$endDate}'";

        // Fetch ALL rows (no SUM)
        $stepData = $operation->get_data(
            "user_step_events",
            "*",
            [],
            "",
            "",
            "",
            "0",
            [],
            [],
            $rawCondition
        );

        /* ================= PREPARE ALL DAYS ================= */

        $daysInMonth = (int)date('t', strtotime($startDate));
        $result = [];

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $day = sprintf('%02d', $i);
            $date = "{$year}-{$month}-{$day}";
            $result[$date] = 0;
        }

        /* ================= AGGREGATE IN PHP ================= */

        if ($stepData['num_rows'] > 0) {
            foreach ($stepData['result'] as $row) {
                $eventDate = date('Y-m-d', strtotime($row->event_time));
                if (isset($result[$eventDate])) {
                    $result[$eventDate] += (int)$row->steps;
                }
            }
        }

        return $this->success_response(
            "Monthly step summary fetched successfully.",
            $result
        );
    }


    public function save_user_goals() {
        $operation = new Operation();

        // Read raw JSON data
        $rawData = file_get_contents("php://input");
        $decodedData = json_decode($rawData, true);

        $token         = trim($decodedData['token'] ?? '');
        $dailyStepGoal = trim($decodedData['daily_step_goal'] ?? '');
        $activityLevel = trim($decodedData['activity_level'] ?? '');
        $weeklyGoal    = trim($decodedData['weekly_goal'] ?? '');

        if (!$token) {
            return $this->error_response("Token is required.");
        }

        // Validate user
        $jwtData = $operation->get_user_id_from_token($token);
        if (!$jwtData['status']) {
            return $this->error_response($jwtData['error']);
        }

        $user_id = $jwtData['user_id'];

        if (!$dailyStepGoal || !$activityLevel || !$weeklyGoal) {
            return $this->error_response("All fields are required.");
        }

        // Optional: Validate activity level
        $allowedLevels = ['Beginner', 'Intermediate', 'Advanced'];
        if (!in_array($activityLevel, $allowedLevels)) {
            return $this->error_response("Invalid activity level.");
        }

        // Check if user already has goals
        $existing = $operation->get_data("user_goals", "*", ["user_id" => $user_id]);

        $data = [
            "daily_step_goal" => $dailyStepGoal,
            "activity_level"  => $activityLevel,
            "weekly_goal"     => $weeklyGoal,
            "updated_at"      => date("Y-m-d H:i:s")
        ];

        if ($existing['num_rows'] == 0) {
            $data["user_id"] = $user_id;
            $data["created_at"] = date("Y-m-d H:i:s");
            $operation->insert_data("user_goals", $data);
        } else {
            $operation->update_data("user_goals", ["user_id" => $user_id], $data);
        }

        return $this->success_response("Goals saved successfully.", $data);
    }

    public function get_user_goals() {
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

        $goals = $operation->get_data("user_goals", "*", ["user_id" => $user_id]);

        if ($goals['num_rows'] == 0) {
            return $this->error_response("No goals found for this user.");
        }

        return $this->success_response("User goals fetched successfully.", $goals['result'][0]);
    }

    public function get_step_summary_today()
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
        $today   = date('Y-m-d');

        $category_id = $this->input_get('category_id');
        $category_id = $category_id ? (int)$category_id : null;

        /* ================= STEP DATA (RAW CONDITION) ================= */

        $stepRawCondition = "user_id = {$user_id} AND DATE(event_time) = '{$today}'";
        if ($category_id) {
            // Include uncategorized (category_id=0) rows so socket-inserted records aren't missed
            $stepRawCondition .= " AND (category_id = {$category_id} OR category_id = 0)";
        }

        // Fetch rows (NOT SUM)
        $stepData = $operation->get_data(
            "user_step_events",
            "*",
            [],
            "",
            "",
            "",
            "0",
            [],
            [],
            $stepRawCondition
        );

        $steps = $kilometre = $kcal = 0;

        if ($stepData['num_rows'] > 0) {
            foreach ($stepData['result'] as $row) {
                $steps     += (int)$row->steps;
                $kilometre += (float)$row->kilometre;
                $kcal      += (float)$row->kcal;
            }
        }

        /* ================= LITRES DATA (RAW CONDITION) ================= */

        $litresRawCondition = "user_id = {$user_id} AND date = '{$today}'";
        if ($category_id) {
            $litresRawCondition .= " AND (earn_category_id = {$category_id} OR earn_category_id = 0)";
        } else {
            $litresRawCondition .= " AND earn_category_id = 1";
        }

        $litresData = $operation->get_data(
            "earn_litties",
            "*",
            [],
            "",
            "",
            "",
            "0",
            [],
            [],
            $litresRawCondition
        );

        $litres = 0;
        if ($litresData['num_rows'] > 0) {
            foreach ($litresData['result'] as $row) {
                $litres += (float)$row->points;
            }
        }

        /* ================= DAILY GOAL ================= */

        $goalData = $operation->get_data(
            "user_goals",
            "daily_step_goal",
            ['user_id' => $user_id]
        );

        $daily_goal = ($goalData['num_rows'] > 0)
            ? (int)$goalData['result'][0]->daily_step_goal
            : 10000;

        /* ================= RESPONSE ================= */

        $result = [
            "date"        => $today,
            "user_id"     =>$user_id,
            "category_id" => $category_id,
            "steps"       => $steps,
            "goal"        => $daily_goal,
            "progress"    => ($daily_goal > 0)
                                ? round(($steps / $daily_goal) * 100, 1)
                                : 0,
            "kilometre"   => round($kilometre, 1),
            "kcal"        => round($kcal, 0),
            "litres"      => round($litres, 2)
        ];

        return $this->success_response(
            "Today's step summary fetched successfully.",
            $result
        );
    }

    public function get_littes_transection(){
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

        /* ================= FETCH TRANSACTIONS ================= */

        $rawCondition = "user_id = {$user_id}";

        $transactions = $operation->get_data("earn_litties", "*", [], "", "id DESC", "", "0", [], [], $rawCondition);


        /* ================= TOTALS & ARRAYS ================= */

        $totalEarn      = 0;
        $referJoinBonus = 0; // earn_category_id = 1
        $claimTotal     = 0; // earn_category_id = 2
        $stepTotal      = 0; // earn_category_id = 3

        $referJoinArr = [];
        $claimArr     = [];
        $stepArr      = [];

        if ($transactions['num_rows'] > 0) {
            foreach ($transactions['result'] as $row) {

                $points = (float)$row->points;
                $totalEarn += $points;

                if ($row->earn_category_id == 1) {
                    $referJoinBonus += $points;
                    $referJoinArr[] = $row;

                } elseif ($row->earn_category_id == 2) {
                    $claimTotal += $points;
                    $claimArr[] = $row;

                } elseif ($row->earn_category_id == 3 || $row->earn_category_id == 0) {
                    // earn_category_id = 0 → uncategorized step earnings from socket
                    $stepTotal += $points;
                    $stepArr[] = $row;
                }
            }
        }

        /* ================= RESPONSE ================= */

        $response = [
            "user_id" => $user_id,
            "totals"  => [
                "total_earn"       => round($totalEarn, 2),
                "refer_join_bonus" => round($referJoinBonus, 2),
                "claim_total"      => round($claimTotal, 2),
                "step_transaction" => round($stepTotal, 2)
            ],
            "transactions" => [
                "refer_join" => $referJoinArr,
                "claim"      => $claimArr,
                "step"       => $stepArr
            ]
        ];

        return $this->success_response(
            "Litties transaction list fetched successfully.",
            $response
        );
    }

    public function get_step_transection_history()
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

        /* ================= FETCH STEP TRANSACTIONS ================= */

        $rawCondition = "user_id = {$user_id}";

        $transactions = $operation->get_data("user_step_events", "*", [], "", "id DESC", "", "0", [], [], $rawCondition);

        /* ================= TOTAL STEPS ================= */

        $total_steps = 0;

        if ($transactions['num_rows'] > 0) {
            foreach ($transactions['result'] as $row) {
                $total_steps += (int)$row->steps;
            }
        }

        /* ================= KM & KCAL (FROM STEPS ONLY) ================= */
        $stepLengthKm = 0.000762;   // step → km
        $kcalPerStep  = 0.05;  // step → kcal

        $total_km   = round($total_steps * $stepLengthKm, 4);
        $total_kcal = round($total_steps * $kcalPerStep, 2);

        /* ================= RESPONSE ================= */

        $response = [
            "user_id" => $user_id,
            "totals" => [
                "total_steps" => $total_steps,
                "total_km" => $total_km,
                "total_kcal" => $total_kcal
            ],
            "transactions" => $transactions['result'] ?? []
        ];

        return $this->success_response(
            "Step transaction history fetched successfully.",
            $response
        );
    }

    public function get_profile_data()
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

        /* ================= USER DETAILS ================= */

        $user = $operation->get_data("users", "id, full_name, email, user_image", ["id" => $user_id]);

        if ($user['num_rows'] == 0) {
            return $this->error_response("User not found.");
        }

        $userData = $user['result'][0];

        /* ================= TOTAL EARN POINTS ================= */

        $earnData = $operation->get_data("earn_litties", "points", ["user_id" => $user_id]);

        $totalEarnPoints = 0;
        if ($earnData['num_rows'] > 0) {
            foreach ($earnData['result'] as $row) {
                $totalEarnPoints += (float)$row->points;
            }
        }

        /* ================= TIER CALCULATION ================= */

        $tiers = $operation->get_data("user_tier", "id, name, points", ["status" => 1], "", "points ASC");

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

        // Calculate progress percentage within current tier
        $prevTierPoints = 0;
        if ($tiers['num_rows'] > 0) {
            foreach ($tiers['result'] as $index => $tier) {
                if ($tier->id == $currentTier->id && $index > 0) {
                    $prevTierPoints = (int)$tiers['result'][$index - 1]->points;
                    break;
                }
            }
        }

        $tierRangeTotal = (int)$currentTier->points - $prevTierPoints;
        $userProgressInTier = $totalEarnPoints - $prevTierPoints;
        $tierProgressPercent = ($tierRangeTotal > 0) ? round(($userProgressInTier / $tierRangeTotal) * 100, 1) : 100;

        $pointsNeeded = $nextTier ? max(0, ($currentTier->points + 1) - $totalEarnPoints) : 0;

        // Time remaining until midnight
        $midnight = strtotime('tomorrow');
        $secondsLeft = $midnight - time();
        $timeRemaining = gmdate("H\h i\m", $secondsLeft);

        $tierData = [
            "current_tier"           => $currentTier ? $currentTier->name : "Bronze",
            "total_points"           => round($totalEarnPoints, 2),
            "current_tier_max"       => $currentTier ? (int)$currentTier->points : 0,
            "tier_progress_percent"  => $tierProgressPercent,
            "next_tier"              => $nextTier ? $nextTier->name : null,
            "next_tier_points"       => $nextTier ? (int)$nextTier->points : null,
            "points_needed"          => $pointsNeeded,
            "time_remaining"         => $timeRemaining,
            "maintain_message"       => "Walk " . $pointsNeeded . " more steps to maintain " . ($currentTier ? $currentTier->name : "Bronze") . " Tier"
        ];

        /* ================= PERSONAL GOAL ================= */

        $goals = $operation->get_data("user_goals", "*", ["user_id" => $user_id]);

        if ($goals['num_rows'] > 0) {
            $goalData = [
                "daily_step_goal" => (int)$goals['result'][0]->daily_step_goal,
                "activity_level"  => $goals['result'][0]->activity_level,
                "weekly_goal"     => (int)$goals['result'][0]->weekly_goal
            ];
        } else {
            // Default goal if not set
            $goalData = [
                "daily_step_goal" => 10000,
                "activity_level"  => "Beginner",
                "weekly_goal"     => 5
            ];
        }

        /* ================= MONTHLY STEP SUMMARY ================= */

        $month = $this->input_get('month') ?: date('m');
        $year  = $this->input_get('year')  ?: date('Y');

        $startDate = "{$year}-{$month}-01";
        $endDate   = date("Y-m-t", strtotime($startDate));

        $rawCondition = "user_id = {$user_id} AND DATE(event_time) BETWEEN '{$startDate}' AND '{$endDate}'";

        $stepData = $operation->get_data(
            "user_step_events",
            "*",
            [],
            "",
            "",
            "",
            "0",
            [],
            [],
            $rawCondition
        );

        // Prepare all days
        $daysInMonth = (int)date('t', strtotime($startDate));
        $dailySteps = [];

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $day = sprintf('%02d', $i);
            $date = "{$year}-{$month}-{$day}";
            $dailySteps[$date] = 0;
        }

        // Aggregate steps
        $totalMonthlySteps = 0;
        if ($stepData['num_rows'] > 0) {
            foreach ($stepData['result'] as $row) {
                $eventDate = date('Y-m-d', strtotime($row->event_time));
                if (isset($dailySteps[$eventDate])) {
                    $dailySteps[$eventDate] += (int)$row->steps;
                    $totalMonthlySteps += (int)$row->steps;
                }
            }
        }

        // Count goal achieved days
        $dailyGoal = $goalData['daily_step_goal'];
        $goalAchievedDays = 0;
        $missedDays = 0;
        $todayDay = (int)date('j');
        $currentMonth = (int)date('m');
        $currentYear = (int)date('Y');

        foreach ($dailySteps as $date => $steps) {
            $dayNum = (int)date('j', strtotime($date));
            $dateMonth = (int)date('m', strtotime($date));
            $dateYear = (int)date('Y', strtotime($date));

            // Only count past days and today
            if (($dateYear < $currentYear) || 
                ($dateYear == $currentYear && $dateMonth < $currentMonth) || 
                ($dateYear == $currentYear && $dateMonth == $currentMonth && $dayNum <= $todayDay)) {
                
                if ($steps >= $dailyGoal) {
                    $goalAchievedDays++;
                } else {
                    $missedDays++;
                }
            }
        }

        $monthlyStepData = [
            "month"              => date('F', strtotime($startDate)),
            "year"               => $year,
            "total_steps"        => $totalMonthlySteps,
            "daily_goal"         => $dailyGoal,
            "goal_achieved_days" => $goalAchievedDays,
            "missed_days"        => $missedDays,
            "daily_steps"        => $dailySteps
        ];

        /* ================= FINAL RESPONSE ================= */

        $response = [
            "user" => [
                "id"         => $userData->id,
                "full_name"  => $userData->full_name,
                "email"      => $userData->email,
                "user_image" => $userData->user_image
            ],
            "tier"          => $tierData,
            "goal"          => $goalData,
            "monthly_steps" => $monthlyStepData
        ];

        return $this->success_response("Profile data fetched successfully.", $response);
    }

    public function get_reffer_and_earn_data()
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

        /* ================= USER REFERRAL CODE ================= */

        $user = $operation->get_data("users", "referal_code", ["id" => $user_id]);

        if ($user['num_rows'] == 0) {
            return $this->error_response("User not found.");
        }

        $referralCode = $user['result'][0]->referal_code;

        /* ================= TOTAL REFERRAL EARN (earn_category_id = 1) ================= */

        $earnCondition = "user_id = {$user_id} AND earn_category_id = 1";
        $earnData = $operation->get_data("earn_litties", "points", [], "", "", "", "0", [], [], $earnCondition);

        $totalReferralEarn = 0;
        if ($earnData['num_rows'] > 0) {
            foreach ($earnData['result'] as $row) {
                $totalReferralEarn += (float)$row->points;
            }
        }

        /* ================= DIRECT REFERRALS ================= */

        $direct = $operation->get_data("users", "id, full_name, referal_code, user_image, status", ["referral_user_id" => $user_id]);

        $directCount = $direct['num_rows'];

        /* ================= NETWORK TREE ================= */

        $totalNetworkCount = 0;
        $networkTree = $this->build_network_tree($user_id, $operation, $totalNetworkCount);

        /* ================= REFERRAL LINK ================= */

        $referralLink = "https://tiny.url/" . $referralCode;

        /* ================= FINAL RESPONSE ================= */

        $response = [
            "user_id"             => $user_id,
            "referal_code"        => $referralCode,
            "referral_link"       => $referralCode,
            "total_referral_earn" => round($totalReferralEarn, 2),
            "direct_count"        => $directCount,
            "total_network_count" => $totalNetworkCount,
            "network_tree"        => $networkTree
        ];

        return $this->success_response("Referral data fetched successfully.", $response);
    }

    private function build_network_tree($user_id, $operation, &$totalCount)
    {
        $children = $operation->get_data(
            "users",
            "id, full_name, referal_code, user_image, status",
            ["referral_user_id" => $user_id]
        );

        $tree = [];

        if ($children['num_rows'] > 0) {
            foreach ($children['result'] as $user) {

                $totalCount++;

                // Count this user's direct referrals for badge
                $directReferrals = $operation->get_data("users", "id", ["referral_user_id" => $user->id]);
                $referralCount = $directReferrals['num_rows'];

                $tree[] = [
                    "id"             => $user->id,
                    "full_name"      => $user->full_name,
                    "referal_code"   => $user->referal_code,
                    "user_image"     => $user->user_image,
                    "status"         => $user->status,
                    "referral_count" => $referralCount,
                    "children"       => $this->build_network_tree($user->id, $operation, $totalCount)
                ];
            }
        }

        return $tree;
    }

    public function get_digital_vault_data()
    {
        $operation = new Operation();

        $token = $this->input_get('token');
        if (!$token) {
            return $this->error_response("Token is required.");
        }

        $jwtData = $operation->get_user_id_from_token($token);
        if (!$jwtData['status']) {
            return $this->error_response($jwtData['error']);
        }

        $user_id = (int)$jwtData['user_id'];
        $today   = date('Y-m-d');

        /* ================= OPTIONAL CATEGORY FILTER ================= */
        $category_id = $this->input_get('category_id');
        $category_id = ($category_id === null || $category_id === '') ? 0 : (int)$category_id;
        // When a specific category is requested, also include uncategorized (0) rows so
        // socket-inserted records aren't missed.
        $catSql    = $category_id > 0 ? " AND (category_id = ? OR category_id = 0) " : "";
        $catParams = $category_id > 0 ? [$category_id] : [];

        /* ================= FILTER RESOLUTION ================= */
        // filter: hourly | daily | weekly | monthly | range (default: daily = today)
        $filter = strtolower((string)$this->input_get('filter'));
        if ($filter === '') $filter = 'daily';

        $date       = $this->input_get('date');        // YYYY-MM-DD (hourly / daily)
        $week_start = $this->input_get('week_start');  // YYYY-MM-DD (weekly, optional)
        $month      = $this->input_get('month');       // 01-12 (monthly)
        $year       = $this->input_get('year');        // YYYY  (monthly)
        $from       = $this->input_get('from');        // YYYY-MM-DD (range)
        $to         = $this->input_get('to');          // YYYY-MM-DD (range)

        $startDate = $today;
        $endDate   = $today;
        $groupBy   = 'day';

        switch ($filter) {
            case 'hourly':
                $startDate = $endDate = $date ?: $today;
                $groupBy = 'hour';
                break;

            case 'daily':
                $startDate = $endDate = $date ?: $today;
                $groupBy = 'day';
                break;

            case 'weekly':
                $wk = $week_start ? date('Y-m-d', strtotime($week_start)) : date('Y-m-d', strtotime('monday this week'));
                $startDate = $wk;
                $endDate   = date('Y-m-d', strtotime("{$wk} +6 days"));
                $groupBy   = 'day';
                break;

            case 'monthly':
                $m = $month ?: date('m');
                $y = $year  ?: date('Y');
                $startDate = sprintf('%04d-%02d-01', $y, $m);
                $endDate   = date('Y-m-t', strtotime($startDate));
                $groupBy   = 'day';
                break;

            case 'range':
                $startDate = $from ?: $today;
                $endDate   = $to   ?: $today;
                $groupBy   = 'day';
                break;

            case 'all':
                $startDate = '1970-01-01';
                $endDate   = $today;
                $groupBy   = 'month';
                break;
        }

        // Sanitize (force YYYY-MM-DD); use !== false so epoch 1970-01-01 (strtotime=0) is preserved
        $sStart = strtotime($startDate);
        $sEnd   = strtotime($endDate);
        $startDate = date('Y-m-d', $sStart !== false ? $sStart : time());
        $endDate   = date('Y-m-d', $sEnd   !== false ? $sEnd   : time());

        $db = $operation->db;

        /* ================= LIFETIME TOTALS (always returned) =================== */
        $r = $db->query(
            "SELECT COALESCE(SUM(points),0) AS total_points FROM earn_litties WHERE user_id = ?",
            [$user_id]
        )->getRow();
        $totalEarnPoints = (float)($r->total_points ?? 0);

        $ls = $db->query(
            "SELECT COALESCE(SUM(steps),0) AS total_steps,
                    COALESCE(SUM(kilometre),0) AS total_km,
                    COALESCE(SUM(kcal),0) AS total_kcal
             FROM user_step_events WHERE user_id = ? {$catSql}",
            array_merge([$user_id], $catParams)
        )->getRow();

        /* ================= FILTERED TOTALS (per filter range) ================= */
        $sr = $db->query(
            "SELECT COALESCE(SUM(steps),0) AS steps,
                    COALESCE(SUM(kilometre),0) AS km,
                    COALESCE(SUM(kcal),0) AS kcal
             FROM user_step_events
             WHERE user_id = ? AND is_date BETWEEN ? AND ? {$catSql}",
            array_merge([$user_id, $startDate, $endDate], $catParams)
        )->getRow();

        $erow = $db->query(
            "SELECT COALESCE(SUM(points),0) AS points
             FROM earn_litties
             WHERE user_id = ? AND DATE(date) BETWEEN ? AND ?",
            [$user_id, $startDate, $endDate]
        )->getRow();
        $rangeLitres = (float)($erow->points ?? 0);

        /* ================= BREAKDOWN (grouped by hour / day / month) =========== */
        if ($groupBy === 'hour') {
            $stepBucket = "DATE_FORMAT(event_time, '%Y-%m-%d %H:00')";
            $earnBucket = "DATE_FORMAT(date, '%Y-%m-%d %H:00')";
            $bucketLabel = "hour";
        } elseif ($groupBy === 'month') {
            $stepBucket = "DATE_FORMAT(event_time, '%Y-%m')";
            $earnBucket = "DATE_FORMAT(date, '%Y-%m')";
            $bucketLabel = "month";
        } else {
            $stepBucket = "is_date";
            $earnBucket = "DATE(date)";
            $bucketLabel = "day";
        }

        $stepBucketsRs = $db->query(
            "SELECT {$stepBucket} AS bucket,
                    COALESCE(SUM(steps),0)     AS steps,
                    COALESCE(SUM(kilometre),0) AS km,
                    COALESCE(SUM(kcal),0)      AS kcal
             FROM user_step_events
             WHERE user_id = ? AND is_date BETWEEN ? AND ? {$catSql}
             GROUP BY bucket
             ORDER BY bucket ASC",
            array_merge([$user_id, $startDate, $endDate], $catParams)
        )->getResult();

        $earnBucketsRs = $db->query(
            "SELECT {$earnBucket} AS bucket,
                    COALESCE(SUM(points),0) AS points
             FROM earn_litties
             WHERE user_id = ? AND DATE(date) BETWEEN ? AND ?
             GROUP BY bucket
             ORDER BY bucket ASC",
            [$user_id, $startDate, $endDate]
        )->getResult();

        $mergedBuckets = [];
        foreach ($stepBucketsRs as $row) {
            $mergedBuckets[$row->bucket] = [
                $bucketLabel => $row->bucket,
                "steps"      => (int)$row->steps,
                "kilometre"  => round((float)$row->km, 1),
                "kcal"       => round((float)$row->kcal, 0),
                "litres"     => 0.0
            ];
        }
        foreach ($earnBucketsRs as $row) {
            if (!isset($mergedBuckets[$row->bucket])) {
                $mergedBuckets[$row->bucket] = [
                    $bucketLabel => $row->bucket,
                    "steps"      => 0,
                    "kilometre"  => 0.0,
                    "kcal"       => 0,
                    "litres"     => 0.0
                ];
            }
            $mergedBuckets[$row->bucket]['litres'] = round((float)$row->points, 2);
        }
        ksort($mergedBuckets);
        $breakdown = array_values($mergedBuckets);

        /* ================= RECENT 10 TRANSACTIONS (within range) =============== */
        $recentRs = $db->query(
            "SELECT * FROM earn_litties
             WHERE user_id = ? AND DATE(date) BETWEEN ? AND ?
             ORDER BY id DESC LIMIT 10",
            [$user_id, $startDate, $endDate]
        )->getResult();

        $recentTransactions = [];
        foreach ($recentRs as $row) {
            $cat = $operation->get_data("earn_category", "category_name", ["id" => $row->earn_category_id]);
            $categoryName = ($cat['num_rows'] > 0) ? $cat['result'][0]->category_name : "";

            $recentTransactions[] = [
                "id"               => $row->id,
                "type"             => $row->type ?? null,
                "earn_category_id" => $row->earn_category_id,
                "category_name"    => $categoryName,
                "points"           => (float)$row->points,
                "description"      => $row->description,
                "date"             => $row->date,
                "time_ago"         => $this->time_ago($row->date)
            ];
        }

        /* ================= LAST 10 STEP TRANSACTIONS (within range) ============ */
        $stepRs = $db->query(
            "SELECT * FROM user_step_events
             WHERE user_id = ? AND is_date BETWEEN ? AND ? {$catSql}
             ORDER BY event_time DESC LIMIT 10",
            array_merge([$user_id, $startDate, $endDate], $catParams)
        )->getResult();

        $stepTransactions = [];
        foreach ($stepRs as $row) {
            $stepTransactions[] = [
                "id"          => $row->id,
                "category_id" => $row->category_id,
                "type"        => $row->type,
                "steps"       => (int)$row->steps,
                "kilometre"   => round((float)$row->kilometre, 1),
                "kcal"        => round((float)$row->kcal, 0),
                "event_time"  => $row->event_time,
                "time_ago"    => $this->time_ago($row->event_time)
            ];
        }

        /* ================= CATEGORY-WISE TOTALS (lifetime, when no category_id filter) === */
        $categoryData = [];
        if ($category_id <= 0) {
            $catRs = $db->query(
                "SELECT category_id,
                        COALESCE(SUM(steps),0)     AS steps,
                        COALESCE(SUM(kilometre),0) AS km,
                        COALESCE(SUM(kcal),0)      AS kcal,
                        COUNT(*)                   AS total_events
                 FROM user_step_events
                 WHERE user_id = ?
                 GROUP BY category_id
                 ORDER BY steps DESC",
                [$user_id]
            )->getResult();

            foreach ($catRs as $row) {
                $categoryData[] = [
                    "category_id"  => (int)$row->category_id,
                    "steps"        => (int)$row->steps,
                    "kilometre"    => round((float)$row->km, 1),
                    "kcal"         => round((float)$row->kcal, 0),
                    "total_events" => (int)$row->total_events
                ];
            }
        }

        /* ================= TODAY LITTIES (always shown) ======================== */
        $trow = $db->query(
            "SELECT COALESCE(SUM(points),0) AS p FROM earn_litties
             WHERE user_id = ? AND DATE(date) = ?",
            [$user_id, $today]
        )->getRow();
        $availableBalance = (float)($trow->p ?? 0);

        /* ================= FINAL RESPONSE ===================================== */
        $response = [
            "user_id" => $user_id,
            "filter"  => [
                "type"        => $filter,
                "start_date"  => $startDate,
                "end_date"    => $endDate,
                "group_by"    => $bucketLabel,
                "category_id" => $category_id
            ],
            "balance_card" => [
                "available_balance"       => round($availableBalance, 2),
                "total_available_balance" => round($totalEarnPoints, 2),
                "total_km"                => round((float)($ls->total_km ?? 0), 1),
                "total_calories"          => round((float)($ls->total_kcal ?? 0), 0),
                "total_steps"             => (int)($ls->total_steps ?? 0)
            ],
            "range_summary" => [
                "steps"     => (int)($sr->steps ?? 0),
                "goal"      => self::DAILY_GOAL,
                "kilometre" => round((float)($sr->km ?? 0), 1),
                "kcal"      => round((float)($sr->kcal ?? 0), 0),
                "litres"    => round($rangeLitres, 2)
            ],
            "breakdown"           => $breakdown,
            "category_data"       => $categoryData,
            "recent_transactions" => $recentTransactions,
            "step_transactions"   => $stepTransactions
        ];

        return $this->success_response("Digital vault data fetched successfully.", $response);
    }

    /**
     * GET get-step-transection-list
     * Params:
     *   token        (required)
     *   filter       hourly | weekly | monthly   (default: hourly)
     *   date         YYYY-MM-DD  (hourly / weekly)
     *   month,year   (monthly)
     *   category_id  (optional)
     *
     * Response: bucketed step transactions with total_steps per bucket.
     * All event_time values are shifted by +05:30 (IST).
     */
    public function get_step_transection_list()
    {
        $operation = new Operation();

        $token = $this->input_get('token');
        if (!$token) {
            return $this->error_response("Token is required.");
        }

        $jwtData = $operation->get_user_id_from_token($token);
        if (!$jwtData['status']) {
            return $this->error_response($jwtData['error']);
        }

        $user_id = (int)$jwtData['user_id'];

        $filter = strtolower((string)$this->input_get('filter'));
        if ($filter === '') $filter = 'hourly';

        $category_id = $this->input_get('category_id');
        $category_id = ($category_id === null || $category_id === '') ? 0 : (int)$category_id;
        // When a specific category is requested, also include uncategorized (0) rows so
        // socket-inserted records aren't missed.
        $catSql    = $category_id > 0 ? " AND (category_id = ? OR category_id = 0) " : "";
        $catParams = $category_id > 0 ? [$category_id] : [];

        $date  = $this->input_get('date');
        $month = $this->input_get('month');
        $year  = $this->input_get('year');

        if (!empty($date)) {
            $dt = \DateTime::createFromFormat('Y-m-d', $date);
            if (!$dt || $dt->format('Y-m-d') !== $date) {
                return $this->error_response("Invalid date format. Use Y-m-d (e.g. 2026-04-14).");
            }
        }

        $offsetSec = 5 * 3600 + 30 * 60; // +05:30

        $db = $operation->db;
        $buckets = [];
        $startDate = null;
        $endDate   = null;

        if ($filter === 'hourly') {
            if (empty($date)) {
                return $this->error_response("date (Y-m-d) is required for hourly filter.");
            }
            $startDate = $endDate = date('Y-m-d', strtotime($date));

            for ($h = 0; $h < 24; $h++) {
                $buckets[$h] = [
                    "hour"        => $h,
                    "label"       => $this->hour_label($h),
                    "total_steps" => 0,
                    "total_km"    => 0.0,
                    "total_kcal"  => 0,
                    "transactions" => []
                ];
            }

            $rs = $db->query(
                "SELECT * FROM user_step_events
                 WHERE user_id = ? AND is_date = ? {$catSql}
                 ORDER BY event_time ASC",
                array_merge([$user_id, $startDate], $catParams)
            )->getResult();

            foreach ($rs as $row) {
                $h = (int)date('G', strtotime($row->event_time));
                $buckets[$h]['total_steps'] += (int)$row->steps;
                $buckets[$h]['total_km']    += (float)$row->kilometre;
                $buckets[$h]['total_kcal']  += (float)$row->kcal;
                $buckets[$h]['transactions'][] = [
                    "id"         => $row->id,
                    "category_id"=> $row->category_id,
                    "type"       => $row->type,
                    "steps"      => (int)$row->steps,
                    "kilometre"  => round((float)$row->kilometre, 1),
                    "kcal"       => round((float)$row->kcal, 0),
                    "event_time" => date('Y-m-d H:i:s', strtotime($row->event_time) + $offsetSec)
                ];
            }
            foreach ($buckets as &$b) {
                $b['total_km']   = round($b['total_km'], 1);
                $b['total_kcal'] = round($b['total_kcal'], 0);
            }
            unset($b);
            $buckets = array_values($buckets);

        } elseif ($filter === 'weekly') {
            if (empty($date)) {
                return $this->error_response("date (Y-m-d) is required for weekly filter.");
            }
            $end = date('Y-m-d', strtotime($date));
            $start = date('Y-m-d', strtotime("{$end} -6 days"));
            $startDate = $start;
            $endDate   = $end;

            $byDate = [];
            for ($i = 0; $i < 7; $i++) {
                $d = date('Y-m-d', strtotime("{$start} +{$i} days"));
                $byDate[$d] = [
                    "date"        => $d,
                    "label"       => date('l', strtotime($d)), // Sunday, Monday, ...
                    "total_steps" => 0,
                    "total_km"    => 0.0,
                    "total_kcal"  => 0,
                    "transactions" => []
                ];
            }

            $rs = $db->query(
                "SELECT * FROM user_step_events
                 WHERE user_id = ? AND is_date BETWEEN ? AND ? {$catSql}
                 ORDER BY event_time ASC",
                array_merge([$user_id, $startDate, $endDate], $catParams)
            )->getResult();

            foreach ($rs as $row) {
                $d = $row->is_date;
                if (!isset($byDate[$d])) continue;
                $byDate[$d]['total_steps'] += (int)$row->steps;
                $byDate[$d]['total_km']    += (float)$row->kilometre;
                $byDate[$d]['total_kcal']  += (float)$row->kcal;
                $byDate[$d]['transactions'][] = [
                    "id"         => $row->id,
                    "category_id"=> $row->category_id,
                    "type"       => $row->type,
                    "steps"      => (int)$row->steps,
                    "kilometre"  => round((float)$row->kilometre, 1),
                    "kcal"       => round((float)$row->kcal, 0),
                    "event_time" => date('Y-m-d H:i:s', strtotime($row->event_time) + $offsetSec)
                ];
            }
            foreach ($byDate as &$b) {
                $b['total_km']   = round($b['total_km'], 1);
                $b['total_kcal'] = round($b['total_kcal'], 0);
            }
            unset($b);
            $buckets = array_values($byDate);

        } elseif ($filter === 'monthly') {
            if (empty($month) || empty($year)) {
                return $this->error_response("month and year are required for monthly filter.");
            }
            $m = $month;
            $y = $year;
            $startDate = sprintf('%04d-%02d-01', (int)$y, (int)$m);
            $endDate   = date('Y-m-t', strtotime($startDate));
            $daysInMonth = (int)date('t', strtotime($startDate));

            $byDate = [];
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $d = sprintf('%04d-%02d-%02d', (int)$y, (int)$m, $i);
                $byDate[$d] = [
                    "day"         => $i,
                    "date"        => $d,
                    "label"       => (string)$i,
                    "total_steps" => 0,
                    "total_km"    => 0.0,
                    "total_kcal"  => 0,
                    "transactions" => []
                ];
            }

            $rs = $db->query(
                "SELECT * FROM user_step_events
                 WHERE user_id = ? AND is_date BETWEEN ? AND ? {$catSql}
                 ORDER BY event_time ASC",
                array_merge([$user_id, $startDate, $endDate], $catParams)
            )->getResult();

            foreach ($rs as $row) {
                $d = $row->is_date;
                if (!isset($byDate[$d])) continue;
                $byDate[$d]['total_steps'] += (int)$row->steps;
                $byDate[$d]['total_km']    += (float)$row->kilometre;
                $byDate[$d]['total_kcal']  += (float)$row->kcal;
                $byDate[$d]['transactions'][] = [
                    "id"         => $row->id,
                    "category_id"=> $row->category_id,
                    "type"       => $row->type,
                    "steps"      => (int)$row->steps,
                    "kilometre"  => round((float)$row->kilometre, 1),
                    "kcal"       => round((float)$row->kcal, 0),
                    "event_time" => date('Y-m-d H:i:s', strtotime($row->event_time) + $offsetSec)
                ];
            }
            foreach ($byDate as &$b) {
                $b['total_km']   = round($b['total_km'], 1);
                $b['total_kcal'] = round($b['total_kcal'], 0);
            }
            unset($b);
            $buckets = array_values($byDate);

        } else {
            return $this->error_response("Invalid filter. Use hourly | weekly | monthly.");
        }

        $response = [
            "user_id"    => $user_id,
            "filter"     => [
                "type"        => $filter,
                "start_date"  => $startDate,
                "end_date"    => $endDate,
                "category_id" => $category_id
            ],
            "buckets"    => $buckets
        ];

        return $this->success_response("Step transaction list fetched successfully.", $response);
    }

    private function hour_label($h)
    {
        $h = (int)$h % 24;
        if ($h === 0)  return "12 AM";
        if ($h < 12)   return $h . " AM";
        if ($h === 12) return "12 PM";
        return ($h - 12) . " PM";
    }

    private function time_ago($datetime)
    {
        $time = strtotime($datetime) + (5 * 3600 + 30 * 60);
        $now = time();
        $diff = $now - $time;

        if ($diff < 60) {
            return "Just now";
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . " min" . ($mins > 1 ? "s" : "") . " ago";
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . " day" . ($days > 1 ? "s" : "") . " ago";
        } elseif ($diff < 2592000) {
            $weeks = floor($diff / 604800);
            return $weeks . " week" . ($weeks > 1 ? "s" : "") . " ago";
        } else {
            return date("M d, Y", $time);
        }
    }

    /* ================= HTTP equivalent of socket StepEvent::handle ================= */
    const STEP_LENGTH_KM = 0.000762;
    const KCAL_PER_STEP  = 0.04;
    const DAILY_GOAL     = 10000;

    public function save_step_event()
    {
        $operation = new Operation();

        $token = $this->input_post('token');
        if (empty($token)) return $this->error_response("Token is required.");

        $jwtData = $operation->get_user_id_from_token($token);
        if (!$jwtData['status']) return $this->error_response($jwtData['error']);

        $user_id     = (int)$jwtData['user_id'];
        // category_id is optional; null/empty/0 → stored as 0 (uncategorized)
        $rawCat      = $this->input_post('category_id');
        $category_id = ($rawCat === null || $rawCat === '' || (int)$rawCat <= 0) ? 0 : (int)$rawCat;
        $steps       = (int)$this->input_post('steps');
        $type        = (string)$this->input_post('type');
        $timestamp   = (int)$this->input_post('timestamp');

        if ($user_id <= 0) {
            return $this->error_response("Invalid user_id");
        }
        if ($this->input_post('steps') === null || $this->input_post('timestamp') === null || $type === '') {
            return $this->error_response("Missing required fields: steps, timestamp, type");
        }

        if ($timestamp <= 0 || $timestamp > time()) {
            $timestamp = time();
        }
        $istTs      = $timestamp + (5 * 3600 + 30 * 60);
        $event_time = date('Y-m-d H:i:s', $istTs);
        $is_date    = date('Y-m-d', $istTs);

        $kilometre = round($steps * self::STEP_LENGTH_KM, 4);
        $kcal      = round($steps * self::KCAL_PER_STEP, 2);

        $lat = $this->input_post('lat');
        $lng = $this->input_post('lng');
        $lat = ($lat === null || $lat === '') ? null : (float)$lat;
        $lng = ($lng === null || $lng === '') ? null : (float)$lng;

        /* INSERT step event */
        $operation->insert_data('user_step_events', [
            'user_id'     => $user_id,
            'category_id' => $category_id,
            'steps'       => $steps,
            'kilometre'   => $kilometre,
            'kcal'        => $kcal,
            'type'        => $type,
            'latitude'    => $lat,
            'longitude'   => $lng,
            'event_time'  => $event_time,
            'is_date'     => $is_date,
        ]);

        /* Today summary (pre-points) to compute totalStepsToday for points */
        $todaySummary = $this->getTodayProgress($operation, $user_id, $category_id, $is_date);
        $totalStepsToday = $todaySummary['steps'];

        $points      = $this->calculatePoints($totalStepsToday);
        $description = "Earned $points points for $totalStepsToday steps";

        /* Upsert earn_litties for today */
        $rawCondition = "user_id = {$user_id}
                        AND earn_category_id = {$category_id}
                        AND DATE(date) = '{$is_date}'";
        $existing = $operation->get_data('earn_litties', 'id', [], '', '', '', '0', [], [], $rawCondition);

        if ($existing['num_rows'] > 0) {
            $operation->update_data(
                'earn_litties',
                ['id' => $existing['result'][0]->id],
                ['points' => $points, 'description' => $description]
            );
        } else {
            $operation->insert_data('earn_litties', [
                'user_id'          => $user_id,
                'earn_category_id' => $category_id,
                'points'           => $points,
                'description'      => $description,
                'date'             => $event_time,
            ]);
        }

        /* Refresh summary including litties */
        $todaySummary = $this->getTodayProgress($operation, $user_id, $category_id, $is_date);

        /* === NOTIFY: daily goal achieved (only once per day, only if toggle ON) === */
        if ($todaySummary['steps'] >= self::DAILY_GOAL) {
            $already = $operation->db->query(
                "SELECT id FROM user_notifications
                 WHERE user_id = ? AND type = 'achievement_notification'
                   AND DATE(created_at) = ?
                   AND JSON_EXTRACT(meta, '$.event') = 'daily_goal_reached'",
                [$user_id, $is_date]
            )->getRow();

            if (!$already) {
                Notification::send(
                    $user_id,
                    'achievement_notification',
                    'Daily goal reached! 🎉',
                    "You've walked {$todaySummary['steps']} steps today — goal of " . self::DAILY_GOAL . " crushed!",
                    ['event' => 'daily_goal_reached', 'steps' => $todaySummary['steps'], 'date' => $is_date]
                );
            }
        }

        return $this->success_response("Step event saved", [
            'user_id'     => $user_id,
            'category_id' => $category_id,
            'steps'       => $todaySummary['steps'],
            'goal'        => $todaySummary['goal'],
            'kilometre'   => $todaySummary['kilometre'],
            'kcal'        => $todaySummary['kcal'],
            'litres'      => $todaySummary['litres'],
        ]);
    }

    private function calculatePoints($steps)
    {
        if ($steps >= 1 && $steps <= 100)    return 0.1;
        if ($steps >= 101 && $steps <= 1000) return 1.0;
        if ($steps >= 1001 && $steps <= 5000) return 5.0;
        if ($steps > 5000)                   return 10.0;
        return 0;
    }

    private function getTodayProgress($operation, $user_id, $category_id, $today)
    {
        $db = $operation->db;

        // category_id = 0 → sum across ALL categories.
        // category_id > 0 → that category PLUS uncategorized (0) rows.
        if ($category_id > 0) {
            $s = $db->query(
                "SELECT COALESCE(SUM(steps),0)     AS total_steps,
                        COALESCE(SUM(kilometre),0) AS total_km,
                        COALESCE(SUM(kcal),0)      AS total_kcal
                 FROM user_step_events
                 WHERE user_id = ? AND (category_id = ? OR category_id = 0) AND is_date = ?",
                [$user_id, $category_id, $today]
            )->getRow();

            $l = $db->query(
                "SELECT COALESCE(SUM(points),0) AS total_litres
                 FROM earn_litties
                 WHERE user_id = ? AND (earn_category_id = ? OR earn_category_id = 0) AND DATE(date) = ?",
                [$user_id, $category_id, $today]
            )->getRow();
        } else {
            $s = $db->query(
                "SELECT COALESCE(SUM(steps),0)     AS total_steps,
                        COALESCE(SUM(kilometre),0) AS total_km,
                        COALESCE(SUM(kcal),0)      AS total_kcal
                 FROM user_step_events
                 WHERE user_id = ? AND is_date = ?",
                [$user_id, $today]
            )->getRow();

            $l = $db->query(
                "SELECT COALESCE(SUM(points),0) AS total_litres
                 FROM earn_litties
                 WHERE user_id = ? AND DATE(date) = ?",
                [$user_id, $today]
            )->getRow();
        }

        return [
            'steps'     => (int)($s->total_steps ?? 0),
            'goal'      => self::DAILY_GOAL,
            'kilometre' => round((float)($s->total_km ?? 0), 1),
            'kcal'      => round((float)($s->total_kcal ?? 0), 0),
            'litres'    => round((float)($l->total_litres ?? 0), 2),
        ];
    }
}
?>