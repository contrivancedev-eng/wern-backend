<?php
namespace App\Controllers\Api\v1;
use App\Controllers\Api\ApiController;
use App\Models\Operation;

class Member_card extends ApiController
{
    public function add_member_card() {
        $operation = new Operation();
        $id = $this->input_post("id");

        $membership_card_number = $this->input_post("membership_card_number");
        $card_points = $this->input_post("card_points");
        
        $table = 'users';
        $searchCondition = array();
        if( strlen($id)>0 ){
            $searchCondition = array_merge($searchCondition,array("id"=>$id));
        }
        if(!empty($id) && $id != "undefined"){

            $exist_data = $operation->get_data($table,"*",array("membership_card_number"=>$membership_card_number),"","");
            if($exist_data["num_rows"]>0){
                if($exist_data["result"][0]->id == $id){
                    $update_arr = array(
                        "membership_card_number" => $membership_card_number,
                        "card_points" => $card_points,
                    );
                    $update = $operation->update_data($table,$searchCondition,$update_arr);
                    if($update){
                        return $this->success_response("status update");
                    }else{
                        return $this->error_response("status unable to update");
                    }
                }else{
                    return $this->error_response("Already Exist!");
                }
                
            }else{
                // <=====================================>
                $update_arr = array(
                    "membership_card_number" => $membership_card_number,
                    "card_points" => $card_points,
                );
                $update = $operation->update_data($table,$searchCondition,$update_arr);
                if($update){
                    return $this->success_response("status update");
                }else{
                    return $this->error_response("status unable to update");
                }
            }

        }else{
        //========================================
            $exist_data = $operation->get_data($table,"*",array("membership_card_number"=>$membership_card_number),"","");
            if($exist_data["num_rows"]>0){
                return $this->error_response("Already Exist!");
            }else{
                $insert_arr = array(
                    "membership_card_number" => $membership_card_number,
                    "card_points" => $card_points,
                );
                $Data = $operation->insert_data($table,$insert_arr);
                if($Data){
                    return $this->success_response("status update");
                }else{
                    return $this->error_response("status unable to update");
                }
            }
        }
    }
    public function add_multiple_member_card() {
        $operation = new Operation();
        //$id = $this->input_post("id");

        $total_card_number = $this->input_post("total_card_number");
        $prefix_name = $this->input_post("prefix_name");
        $card_points = $this->input_post("card_points");
        
        $final = '';
        $table = 'users';
        for($i=0;$i<$total_card_number;$i++){
            $new_random = strtoupper($this->random_strings(3));
            $new_random_digit = $this->generateFourDigitCode();
            $create_card = strtoupper($prefix_name).'-'.$new_random.'-'. $new_random_digit;
            // check this new card is Exist or not
            $exist_data = $operation->get_data($table,"*",array("membership_card_number"=>$create_card),"","");
            if($exist_data["num_rows"]>0){
                $final--;
                //return $this->error_response("Already Exist!");
            }else{
                $final++;
                $insert_arr = array(
                    "membership_card_number" => $create_card,
                    "card_points" => $card_points,
                );
                $Data = $operation->insert_data($table,$insert_arr);
                
            }

        }
        if( $total_card_number == $final){
            return $this->success_response("Successfully Created");
        }else{
            return $this->error_response("Try againg Something Wrong!");
        }
                    
    }
    public function full_user_data() {
        $operation = new Operation();
        $user_id = $this->input_get("user_id");
        $status = $this->input_get("status");
        $search = $this->input_get("search");
        $limit = $this->input_get("limit") !== '' ? (int)$this->input_get("limit") : 10;
        $viewAll = (int)($this->input_get("view_all") ?? 0);
        $skip = (int)($this->input_get("last_row") ?? 0);
    
        $conditions = [];
        $like_conditions = [];
    
        if ($user_id) $conditions['id'] = $user_id;
        if ($status) $conditions['status'] = $status;
        
        if ($search) $like_conditions['membership_card_number'] = $search;
    
        if ($viewAll) {
            $limit = '';
            $skip = 0;
        }
        $result = $operation->get_data("users", "*", $conditions, "update_on", "DESC", $limit, $skip, $like_conditions);
    
        if ($result['num_rows'] > 0) {
            $data = array_map(function($user) use ($operation) {
                $referral_code = '';
                $referred_by = '';
                $total_referral = '';
        
                if (!empty($user->referral_id) && $user->referral_id > 0) {
                    $referrel_result = $operation->get_data("refferal_code", "*", ["id" => $user->referral_id], "", "");
                    if ($referrel_result['num_rows'] > 0) {
                        $referral_code = $referrel_result['result'][0]->refferal_code;
        
                        if (!empty($referrel_result['result'][0]->user_id) && $referrel_result['result'][0]->user_id > 0) {
                            $referrel_userDetails = $operation->get_data("users", "*", ["id" => $referrel_result['result'][0]->user_id], "", "");
                            if ($referrel_userDetails['num_rows'] > 0) {
                                $referred_by = $referrel_userDetails['result'][0]->full_name;
                            }
                        }
                    }
                }
                if (!empty($user->id) && $user->id > 0) {
                    $referrel_total = $operation->get_data("refferal_code", "*", ["user_id" => $user->id], "", "");
                    if ($referrel_total['num_rows'] > 0) {
                        $total_referral = $referrel_total['num_rows'];
                    }
                }
        
                return [
                    'user_id' => $user->id,
                    'full_name' => $user->full_name,
                    'country_code' => $user->country_code,
                    'phone_number' => $user->phone_number,
                    'email' => $user->email,
                    'referral_id' => $user->referral_id,
                    'refferal_code' => $referral_code,
                    'reffer_by' => $referred_by,
                    'total_reffer' => $total_referral,
                    // 'user_image' => ASSIST_PATH.'images/'.$user->user_image,
                    'membership_card_number' => $user->membership_card_number,
                    'card_points' => $user->card_points,
                    'status' => $user->status
                ];
            }, $result['result']);
        
    
            $total_count = $viewAll ? $result['num_rows'] : $operation->get_data("users", '*', $conditions, "", "", "", "", $like_conditions)['num_rows'];
            $pagination = [
                'load_more' => $viewAll ? 0 : (($skip + $limit) < $total_count ? 1 : 0),
                'last_row' => $viewAll ? $total_count : ($skip + $limit),
                'total_pages' => $viewAll ? 1 : ceil($total_count / $limit),
            ];
    
            return $this->success_response("Member card list found.", $data, $pagination);
        } else {
            return $this->error_response("Member card list not found!");
        }
    }
    public function add_settings() {
        $operation = new Operation();
        $id = $this->input_post("id");

        //$sign_up_points = $this->input_post("sign_up_points");
        $sign_up_bonus_new_user = $this->input_post("sign_up_bonus_new_user");
        $sign_up_bonus_existing_user = $this->input_post("sign_up_bonus_existing_user");
        $without_refferal_bonus_new_user = $this->input_post("without_refferal_bonus_new_user");
        $twilio_sid = $this->input_post("twilio_sid");
        $twilio_token = $this->input_post("twilio_token");
        $twilio_phone = $this->input_post("twilio_phone");
        //<=====================================>
        $table = "settings";
        $searchCondition = array();
        if( strlen($id)>0 ){
            $searchCondition = array_merge($searchCondition,array("id"=>$id));
        }
        //<=====================================>
        if(!empty($id) && $id != "undefined"){
            $update_arr = array(
                //"sign_up_points" => $sign_up_points,
                "sign_up_bonus_new_user" => $sign_up_bonus_new_user,
                "sign_up_bonus_existing_user" => $sign_up_bonus_existing_user,
                "without_refferal_bonus_new_user" => $without_refferal_bonus_new_user,
                "twilio_sid" => $twilio_sid,
                "twilio_token" => $twilio_token,
                "twilio_phone" => $twilio_phone,
            );
            $update = $operation->update_data($table,$searchCondition,$update_arr);
            if($update){
                return $this->success_response("status update");
            }else{
                return $this->error_response("status unable to update");
            }
        }else{
            //========================================
            $insert_arr = array(
                //"sign_up_points" => $sign_up_points,
                "sign_up_bonus_new_user" => $sign_up_bonus_new_user,
                "sign_up_bonus_existing_user" => $sign_up_bonus_existing_user,
                "without_refferal_bonus_new_user" => $without_refferal_bonus_new_user,
                "twilio_sid" => $twilio_sid,
                "twilio_token" => $twilio_token,
                "twilio_phone" => $twilio_phone,
            );
            $Data = $operation->insert_data($table,$insert_arr);
            if($Data){
                return $this->success_response("status update");
            }else{
                return $this->error_response("status unable to update");
            }
        }
    }
    public function settings_details() {
        $operation = new Operation();
        $id = $this->input_get("id");
       
        $searchCondition = array();
        if( strlen($id)>0 ){
            $searchCondition = array_merge($searchCondition,array("id"=>$id));
        }
        $total_data = $operation->get_data("settings","*",$searchCondition,"","");
        if($total_data["num_rows"]>0){
            $data_count = 0;
            $data_arr = array();
            foreach($total_data["result"] as $datalist){
                $data_arr[$data_count]["id"] = $datalist->id;
                //$data_arr[$data_count]['sign_up_points'] = $datalist->sign_up_points;
                $data_arr[$data_count]['sign_up_bonus_new_user'] = $datalist->sign_up_bonus_new_user;
                $data_arr[$data_count]['sign_up_bonus_existing_user'] = $datalist->sign_up_bonus_existing_user;
                $data_arr[$data_count]['without_refferal_bonus_new_user'] = $datalist->without_refferal_bonus_new_user;
                $data_arr[$data_count]['twilio_sid'] = $datalist->twilio_sid;
                $data_arr[$data_count]['twilio_token'] = $datalist->twilio_token;
                $data_arr[$data_count]['twilio_phone'] = $datalist->twilio_phone;
                $data_count++;
            }
            return $this->success_response("list found..",$data_arr);
        }else{
            return $this->error_response("list not found!");
        }
    }
    public function user_referral_data() {
        $operation = new Operation();
    
        $user_id = $this->input_get('user_id');
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
       
        foreach($referrals['result'] as $key=>$referralsList) {
            $referrals_user = $operation->get_data("users", "*", ["referral_id"=>$referralsList->id], "", "");
            
            if($referrals_user['num_rows'] > 0){
                //print_r($referrals_user['result']);
                $referralsList->referral_usedby = $referrals_user['result'][0]->full_name;
            }else{
                $referralsList->referral_usedby = '';
            }
        }
      
        // echo "<pre>";
        // print_r($referrals['result']);
        // die();
    
        if ($referrals['num_rows'] == 0) {
            return $this->error_response("No referral codes found for this month.");
        }
    
        return $this->success_response("Referral codes fetched successfully.", $referrals['result']);
    }   
    public function user_lottery_data() {
        $operation = new Operation();
    
        $user_id = $this->input_get('user_id');
        $input_year = $this->input_get('year');
        $input_month = $this->input_get('month');
    
        $year = !empty($input_year) ? $input_year : date('Y');
        $month = !empty($input_month) ? $input_month : date('m');
        // Format: YYYY-MM (e.g., '2025-04')
        //$yearMonth = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
        // First fetch lottery winers =========================
       
        // $rawCondition = "SELECT lw.id, lw.lottery_id, lw.user_id, lw.points FROM lottery_winers AS lw JOIN 
        // monthly_lottery AS ml ON lw.lottery_id = ml.id WHERE lw.user_id = 1 AND ml.lottery_date = '2025-04-30'";
        $db = \Config\Database::connect();

        $builder = $db->table('lottery_winers lw');
        $builder->select('lw.id, lw.lottery_id, lw.user_id, lw.points, ml.status, ml.title, ml.lottery_date, u.full_name');
        $builder->join('monthly_lottery ml', 'lw.lottery_id = ml.id');
        $builder->join('users u', 'lw.user_id = u.id'); 
        //$builder->where('lw.user_id', $user_id);
        // Apply user_id conditionally
        if (!empty($user_id)) {
            $builder->where('lw.user_id', $user_id);
        }
        //$builder->like('ml.lottery_date', $yearMonth, 'after'); // Matches '2025-04%'
        $builder->where("YEAR(ml.lottery_date)", $year);
        $builder->where("MONTH(ml.lottery_date)", $month);

        //$builder->orderBy('lw.id', 'DESC'); // or ASC if needed


        $query = $builder->get();
        $result = $query->getResult();
        
        $totalPoints = 0;
        foreach ($result as $row) {
            $totalPoints += (int) $row->points;
        }
        $returnArr = array(
            "lottery_list" => $result,
            "totalPoints" => $totalPoints,
        );
        // echo "<pre>";
        // print_r("//=============================");
        // print_r($returnArr);
        //echo $totalPoints;
        //die();
        if (sizeof($returnArr) > 0) {
            return $this->success_response("Lottery codes fetched successfully.", $returnArr);
            
        }else{
            return $this->error_response("No Lottery codes found for this month.");
        }
    } 
    public function edit_user() {
        $operation =  new Operation();
        $id = $this->input_post("id");
        $name = $this->input_post("name");
        $email = $this->input_post("email");
        $code = $this->input_post("code");
        $phone = $this->input_post("phone");
        $membership_card_number = $this->input_post("membership_card_number");

        // Create membership card number
        $first_part = strtoupper(substr(str_replace(' ', '', $name), 0, 4));
        $phone_clean = str_replace(' ', '', $phone);
        $last_part = substr($phone_clean, -4);
    
        $membership_card_number = "LBT-" . $first_part . "-" . $last_part;
        
        $table = 'users';
        $searchCondition = array();
        if( strlen($id)>0 ){
            $searchCondition = array_merge($searchCondition,array("id"=>$id));
        }
        if(!empty($id) && $id != "undefined"){
            // <=====================================>
            $update_arr = array(
                "full_name" => $name,
                "email" => $email,
                "country_code" => $code,
                "phone_number" => $phone,
                "membership_card_number" => $membership_card_number,
            );
            $update = $operation->update_data($table,$searchCondition,$update_arr);
            if($update){
                return $this->success_response("status update");
            }else{
                return $this->error_response("status unable to update");
            }
        }else{
            //========================================
            // $insert_arr = array(
            //     "name" => $name,
            //     "email" => $email,
            //     "code" => $code,
            //     "phone" => $phone,
            //     "card" => $card,
            // );
            // $Data = $operation->insert_data($table,$insert_arr);
            // if($Data){
            //     return $this->success_response("status update");
            // }else{
            //     return $this->error_response("status unable to update");
            // }
        }
    }
    public function random_strings($length_of_string){
        $str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        return substr(str_shuffle($str_result),
                        0, $length_of_string);

    }
    public function add_monthly_lottery() {
        $operation =  new Operation();
        $id = $this->input_post("id");
        $title = $this->input_post("title");
        $required_number = $this->input_post("required_number");
        $date_1 = $this->input_post("date_1");
        $date_2 = $this->input_post("date_2");
        // echo $title;
        // die();
        //<=====================================>
        $table = "monthly_lottery";
        $searchCondition = array();
        if( strlen($id)>0 ){
            $searchCondition = array_merge($searchCondition,array("id"=>$id));
        }
        //<=====================================>
        if(!empty($id) && $id != "undefined"){
            $update_arr = array(
                "title" => $title,
                "joining_points" => $required_number,
                "lottery_date" => $date_1,
                "joining_date" => $date_2,
                "status" => 1,
            );
            $update = $operation->update_data($table,$searchCondition,$update_arr);
            if($update){
                return $this->success_response("status update");
            }else{
                return $this->error_response("status unable to update");
            }
        }else{
        //========================================
            $insert_arr = array(
                "title" => $title,
                "joining_points" => $required_number,
                "lottery_date" => $date_1,
                "joining_date" => $date_2,
                "status" => 1,
            );
            $Data = $operation->insert_data($table,$insert_arr);
            if($Data){
                return $this->success_response("status update");
            }else{
                return $this->error_response("status unable to update");
            }
        }
    }
    public function add_admin_settings() {
        $operation =  new Operation();
        $id = $this->input_post("id");

        $name = $this->input_post("name");
        $email = $this->input_post("email");
        $phone = $this->input_post("phone");
        // echo $name;
        // die();
        //<=====================================>
        $new_random = $this->random_strings(6);
        //<=====================================>
        $table = "login_member";
        $searchCondition = array();
        if( strlen($id)>0 ){
            $searchCondition = array_merge($searchCondition,array("id"=>$id));
        }
        //<=====================================>
        if(!empty($id) && $id != "undefined"){
            if(!empty($_FILES["fileToUpload"])){
                $fileToUpload = $_FILES["fileToUpload"];
                $coverFile_name = $this->image_upload($fileToUpload,$new_random,$table,$id);
            }else{
                $coverFile_name = $this->image_upload(0,0,$table,$id);
            }
            $update_arr = array(
                "fullname" => $name,
                "email" => $email,
                "phone" => $phone,
                "profile_photo" => $coverFile_name,
            );
            $update = $operation->update_data($table,$searchCondition,$update_arr);
            if($update){
                return $this->success_response("status update");
            }else{
                return $this->error_response("status unable to update");
            }
        }else{
            //========================================
            if(!empty($_FILES["fileToUpload"])){
                    $fileToUpload = $_FILES["fileToUpload"];
                    $coverFile_name = $this->image_upload($fileToUpload,$new_random,$table,0);
            }else{
                $coverFile_name = "";
            }
            $insert_arr = array(
                "fullname" => $name,
                "email" => $email,
                "phone" => $phone,
                "profile_photo" => $coverFile_name,
            );
            //echo "<pre>"; print_r($insert_arr); die();
            $Data = $operation->insert_data($table,$insert_arr);
            if($Data){
                return $this->success_response("status update");
            }else{
                return $this->error_response("status unable to update");
            }
        }
    }
    public function admin_settings_list (){
        $operation =  new Operation();
        $id = $this->input_post("id");
        $searchCondition = array();
        if( strlen($id)>0 ){
            $searchCondition = array_merge($searchCondition,array("id"=>$id));
        }
        $total_data = $operation->get_data('login_member',"*",$searchCondition,"","");
        if($total_data["num_rows"]>0){
            $data_count = 0;
            $data_arr = array();
            foreach($total_data["result"] as $datalist){
                $data_arr[$data_count]["id"] = $datalist->id;
                $data_arr[$data_count]['name'] = $datalist->fullname;
                $data_arr[$data_count]['email'] = $datalist->email;
                $data_arr[$data_count]['phone'] = $datalist->phone;
                $data_arr[$data_count]['password'] = $datalist->password;
                $data_arr[$data_count]['role'] = $datalist->role;
                $data_arr[$data_count]['status'] = $datalist->status;
                $data_arr[$data_count]['create_on'] = $datalist->create_on;
                $data_arr[$data_count]['file_path'] = ADMIN_ASSIST_PATH.$datalist->profile_photo;
                $data_count++;
            }
            return $this->success_response("list found..",$data_arr);
        }else{
            return $this->error_response("list not found!");
        }
    }
    function generateFourDigitCode() {
        return rand(100, 999);
    }
    public function date_format() {
        // Set the default timezone if needed
        date_default_timezone_set('Asia/Kolkata'); // e.g., 'Asia/Kolkata'

        // Format the current date
        $currentDate = strtoupper(date('d F Y'));
        return $currentDate;
    }
    public function image_upload($fileToUpload,$new_random,$table,$id){
        $operation =  new Operation();
        if(!empty($_FILES['fileToUpload'])){
            if($_FILES['fileToUpload']['size']!=0){
                $cover_img = $_FILES['fileToUpload'];
                $cover_img_tmp = $_FILES['fileToUpload']['tmp_name'];
                $name = (explode(".", $cover_img['name']));
                $ext = end($name);
                $coverFile_name = md5($new_random).'.'.$ext;
                $upload_path = "public/admin/assets/";    // Path Image folder 
                $uploaded_cover_img = move_uploaded_file($cover_img_tmp, $upload_path.$coverFile_name);
            }
        }else{
            $Details_data = $operation->get_data($table,"*",array("id"=>$id),"","");
            if($Details_data['num_rows']>0){
                $coverFile_name = $Details_data['result'][0]->profile_photo;  //Table image Field Name
            }
        
        }
    return $coverFile_name;
    }
    public function announce_random() {
        $operation =  new Operation();
        $id = $this->input_get("id");
        $date = $this->input_get("date");
        $db = \Config\Database::connect();
        if(!empty($id)){
            $builder = $db->table('monthly_lottery ml');
            $builder->select('ml.id, ml.title, ml.lottery_date, ml.joining_points, jlu.user_id, jlu.lottery_id, ur.full_name');
            $builder->join('join_lottery_user jlu', 'ml.id = jlu.lottery_id');
            $builder->join('users ur', 'jlu.user_id = ur.id');
            // Date filters
            if(!empty($id)){
                $builder->where("ml.id", $id);
            }
            if(!empty($date)){
                $builder->where("ml.lottery_date", $date);
            }
            
            $query = $builder->get();
            $result = $query->getResult();
            //echo "<pre>";print_r($result);die();
            if(sizeof($result) > 0){
                $totalPoints = 0;
                $lottery_id = 0;
                $user_ids = [];
                foreach ($result as $row) {
                    $totalPoints += $row->joining_points;
                    $lottery_id = $row->lottery_id;
                    $user_ids[] = $row->user_id;
                    
                }
                //=================================
                $random_keys = [];

                $randoms_keys = array_rand($user_ids, 3);
                shuffle($randoms_keys);
                foreach ($randoms_keys as $key) {
                    $random_keys[] = $user_ids[$key];
                }

                // Get the actual user IDs 
                $winner_user_ids = $random_keys;
                $points_div = 0;
                foreach ($winner_user_ids as $index=>$winner_row) {
                    if($index == 0){
                        $points_div = $totalPoints/100 * 50;
                    }else if($index == 1){
                        $points_div = $totalPoints/100 * 30;
                    }else if($index == 2){
                        $points_div = $totalPoints/100 * 20;
                    }
                    $winnerData[] = [
                        'win_id' => $winner_row,
                        'points' => $points_div,
                        'lottery_id' => $lottery_id,
                    ];

                }

            
                if(sizeof($winnerData) > 0){
                    $exist_data = $operation->get_data('lottery_winers',"*",array("lottery_id"=>$lottery_id),"","");
                    if($exist_data["num_rows"]>0){
                        return $this->error_response("Data Exist!");
                    }else{
                        
                        foreach($winnerData as $row){
                            // =========== 1st ================
                            $insert_arr = array(
                                "lottery_id" => $row['lottery_id'],
                                "user_id" => $row['win_id'],
                                "points" => $row['points'],
                            
                            );
                            //print_r($insert_arr);
                            $Data = $operation->insert_data('lottery_winers',$insert_arr);
                            // ============ 2nd ================
                            $insert_arr1 = array(
                                "type" => 1,
                                "user_id" => $row['win_id'],
                                "points" => $row['points'],
                                "description" => "You've earned ".$row['points']." points for Winning Achivements.",
                            
                            );
                            //print_r($insert_arr1);
                            $Data2 = $operation->insert_data('points',$insert_arr1);
                        }
                                    
                        if($Data){
                            return $this->success_response("Successfully Insert");
                        }else{
                            return $this->error_response("Unable to Process!");
                        }
                    }
                    
                }
            }else{
                return $this->error_response("Data not exist for this Id");
            }
        }else{
            return $this->error_response("Put a Valid ID");
        }

    }
    
}
?>