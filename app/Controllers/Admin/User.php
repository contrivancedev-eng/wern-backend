<?php

namespace App\Controllers\Admin;
use App\Controllers\Admin\AdminController;
use App\Models\Operation;

class User extends AdminController{
    

    public function user_list() {
        $operation = new Operation();
        $data = array('pageTitle' => 'LBT First Track');
        $apiUrl = 'full-user-data';
        $apiData = array('view_all' => 1,'status' =>1);
        $result = $operation->get_api($apiUrl, $apiData);
        $data['userList'] = !empty($result->data) ? $result->data : array();

        $this->load_admin_view(['Admin/user_list'], $data, [], "afterLogin");
    }
    public function user_details() {
        $operation = new Operation();
        $id = $this->input_get("id");

        $data = array('pageTitle' => 'LBT First Track');
        ///////////////////////////////////////////////////
        $apiUrl = 'full-user-data';
        $apiData = array('view_all' => 1,"user_id"=>$id,'status' =>1);
        $result = $operation->get_api($apiUrl, $apiData);
        $data['userDetails'] = !empty($result->data) ? $result->data : array();
        ////////////////////////////////////////////////////
        $apiUrl = 'user-referral-data';
        $apiData = array("user_id"=>$id,'status' =>1);
        $result = $operation->get_api($apiUrl, $apiData);
        $data['user_referral_list'] = !empty($result->data) ? $result->data : array();
        /////////////////////////////////////////////////////
        //echo "<pre>";print_r($data);die();
        $this->load_admin_view(['Admin/user_details'], $data, [], "afterLogin");
    }
    public function user_winlottery_list() {
        $operation = new Operation();
        $id = $this->input_get("id");

        $data = array('pageTitle' => 'LBT First Track');
         ///////////////////////////////////////////////////
         $apiUrl = 'full-user-data';
         $apiData = array('view_all' => 1,"user_id"=>$id,'status' =>1);
         $result = $operation->get_api($apiUrl, $apiData);
         $data['userDetails'] = !empty($result->data) ? $result->data : array();
         ////////////////////////////////////////////////////
        ///////////////////////////////////////////////////
        $apiUrl = 'user-lottery-data';
        $apiData = array("user_id"=>$id,'status' =>1);
        $result = $operation->get_api($apiUrl, $apiData);
        $data['lotterywinList'] = !empty($result->data) ? $result->data : array();
        ////////////////////////////////////////////////////
       
        //echo "<pre>";print_r($data);die();
        $this->load_admin_view(['Admin/user_lottery_list'], $data, [], "afterLogin");
    }

    public function member_card_list() {

        $operation = new Operation();
        $data = array('pageTitle' => 'LBT First Track');

        $apiUrl = 'get-all-member-card';
        $apiData = array('view_all' => 1);
        $result = $operation->get_api($apiUrl, $apiData);
        $data['cardList'] = !empty($result->data) ? $result->data : array();

        $this->load_admin_view(['Admin/member_card_list'], $data, [], "afterLogin");
    }

    public function referral_code_list() {
        $operation = new Operation();
        $data = array('pageTitle' => 'LBT First Track');

        $apiUrl = 'get-all-referral-code';
        $apiData = array('view_all' => 1);
        $result = $operation->get_api($apiUrl, $apiData);
        $data['reffralList'] = !empty($result->data) ? $result->data : array();

        $this->load_admin_view(['Admin/referral_code_list'], $data, [], "afterLogin");
    }
    public function settings() {
        $operation = new Operation();
        $data = array('pageTitle' => 'LBT First Track');

        $apiUrl = 'settings-details';
        $apiData = array();
        $result = $operation->get_api($apiUrl, $apiData);
        $data['details'] = !empty($result->data) ? $result->data : array();

        $apiUrl = 'admin-settings-list';
        $apiData = array();
        $result = $operation->get_api($apiUrl, $apiData);
        $data['admin_details'] = !empty($result->data) ? $result->data : array();

        $this->load_admin_view(['Admin/settings'], $data, [], "afterLogin");
    }
    public function join_club() {
        $operation = new Operation();
        $data = array('pageTitle' => 'LBT First Track');
        $this->load_admin_view(['Admin/join_club'], $data, [], "afterLogin");
    }


    public function join_club_new() {
        $operation = new Operation();
        $data = array('pageTitle' => 'LBT First Track');
        
        $apiUrl = 'get-all-club';


        // $current_month = date('m');
        // $current_year = date('Y');
        // $apiData = array('month' =>$current_month, 'year' =>$current_year, 'view_all' => 1);
        // $result = $operation->get_api($apiUrl, $apiData);
        // $data['clubList'] = !empty($result->data) ? $result->data : array();


        $this->load_admin_view(['Admin/join_club_new'], $data, [], "afterLogin");
    }
}
?>