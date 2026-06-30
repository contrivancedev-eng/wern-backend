<?php

$routes->group('api', static function ($routes) {
    $routes->get('test', 'Api\v1\Test::test');


    //================== AUTH ==========================================
    $routes->post('user-registration', 'Api\v1\Auth::user_registration');
    $routes->post('verify-registration-account', 'Api\v1\Auth::verify_account');
    $routes->post('resend-verify-otp', 'Api\v1\Auth::verify_resend_otp');
    $routes->post('user-login-action', 'Api\v1\Auth::user_login_action');

    $routes->post('forgot-password-send-otp', 'Api\v1\Auth::forgot_password_send_otp');
    $routes->post('forgot-password-verify-otp', 'Api\v1\Auth::forgot_password_verify_otp');
    $routes->post('forgot-password-change', 'Api\v1\Auth::forgot_password_change');

    $routes->post('change-password-user', 'Api\v1\Auth::change_password_user');
    //================== AUTH ==========================================
    $routes->get('get-user-details', 'Api\v1\User::get_user_details');
    $routes->get('get-refferal-users-history', 'Api\v1\User::get_refferal_users_history');


    //================== Daily Claim ==========================================
    $routes->get('user-daily-claim', 'Api\v1\User::daily_claim');
    $routes->get('get-daily-claim-status', 'Api\v1\User::get_daily_claim_status');

    //==================Points ==========================================
    $routes->get('get-points-summary', 'Api\v1\User::get_points_summary');


    //================== STEPS ==========================================
    $routes->get('get-step-summary-monthly', 'Api\v1\Step::get_step_summary_monthly');
    $routes->get('get-step-summary-today', 'Api\v1\Step::get_step_summary_today');
    $routes->get('get-littes-transection', 'Api\v1\Step::get_littes_transection');
    $routes->get('get-step-transection-history', 'Api\v1\Step::get_step_transection_history');
    $routes->get('get-step-transection-list', 'Api\v1\Step::get_step_transection_list');


    $routes->get('user-activity', 'Api\v1\User::user_activity');



    //================== Step Event (HTTP equivalent of socket) ========
    $routes->post('save-step-event', 'Api\v1\Step::save_step_event');

    //================== Notification =================================
    $routes->get('get-notification-settings',    'Api\v1\Notification::get_notification_settings');
    $routes->post('update-notification-settings','Api\v1\Notification::update_notification_settings');
    $routes->get('get-notifications',            'Api\v1\Notification::get_notifications');
    $routes->post('mark-notification-read',      'Api\v1\Notification::mark_notification_read');
    $routes->post('save-fcm-token',              'Api\v1\Notification::save_fcm_token');

    //================== Goals ==========================================
    $routes->post('save-user-goals', 'Api\v1\Step::save_user_goals');
    $routes->get('get-user-goals', 'Api\v1\Step::get_user_goals');


    //=====================MENU======================================
    $routes->get('get-profile-data', 'Api\v1\Step::get_profile_data');
    $routes->get('get-reffer-and-earn-data', 'Api\v1\Step::get_reffer_and_earn_data');
    $routes->get('get-digital-vault-data', 'Api\v1\Step::get_digital_vault_data');


    $routes->post('update-user-image', 'Api\v1\User::update_user_image');
    $routes->get('get-weather-by-location', 'Api\v1\User::get_weather_by_location');



    $routes->post('delete-all-data', 'Api\v1\User::delete_all_data');
    $routes->options('delete-all-data', static function () {
        return service('response')->setStatusCode(200);
    });


    //================== Community ===================================
    $routes->post('community-create-post',   'Api\v1\Community::create_post');
    $routes->get('community-feed',           'Api\v1\Community::feed');
    $routes->get('community-my-posts',       'Api\v1\Community::my_posts');
    $routes->get('community-post-detail',    'Api\v1\Community::post_detail');
    $routes->post('community-update-post',   'Api\v1\Community::update_post');
    $routes->post('community-delete-post',   'Api\v1\Community::delete_post');
    $routes->post('community-like-post',     'Api\v1\Community::like_post');
    $routes->post('community-unlike-post',   'Api\v1\Community::unlike_post');
    $routes->post('community-add-comment',   'Api\v1\Community::add_comment');
    $routes->get('community-comments',       'Api\v1\Community::get_comments');
    $routes->post('community-update-comment','Api\v1\Community::update_comment');
    $routes->post('community-delete-comment','Api\v1\Community::delete_comment');
    $routes->post('community-save-post',     'Api\v1\Community::save_post');
    $routes->post('community-unsave-post',   'Api\v1\Community::unsave_post');
    $routes->get('community-saved-posts',    'Api\v1\Community::saved_posts');
    $routes->post('community-report-post',   'Api\v1\Community::report_post');

    //================== Events ======================================
    $routes->get('event-list',                 'Api\v1\Event::event_list');
    $routes->get('event-detail',               'Api\v1\Event::detail');
    $routes->get('event-my-events',            'Api\v1\Event::my_events');
    $routes->post('event-create',              'Api\v1\Event::create');
    $routes->post('event-update',              'Api\v1\Event::update');
    $routes->post('event-delete',              'Api\v1\Event::delete');
    $routes->post('event-rsvp',                'Api\v1\Event::rsvp');
    $routes->get('event-message-thread',       'Api\v1\Event::message_thread');
    $routes->post('event-send-message',        'Api\v1\Event::send_message');
    $routes->get('event-conversations',        'Api\v1\Event::conversations');

    //================== Challenges ==================================
    $routes->get('challenge-list',               'Api\v1\Challenge::challenge_list');
    $routes->get('challenge-detail',             'Api\v1\Challenge::detail');
    $routes->get('challenge-my-challenges',      'Api\v1\Challenge::my_challenges');
    $routes->get('challenge-leaderboard',        'Api\v1\Challenge::leaderboard');
    $routes->post('challenge-join',              'Api\v1\Challenge::join');
    $routes->post('challenge-leave',             'Api\v1\Challenge::leave');
    $routes->post('challenge-update-progress',   'Api\v1\Challenge::update_progress');
    $routes->post('challenge-create',            'Api\v1\Challenge::create');
    $routes->post('challenge-update',            'Api\v1\Challenge::update');
    $routes->post('challenge-delete',            'Api\v1\Challenge::delete');

    //================== Reviews =====================================
    $routes->post('submit-review',        'Api\v1\Review::submit_review');
    $routes->get('admin-reviews-stats',   'Api\v1\AdminReviews::stats');
    $routes->get('admin-reviews-by-user', 'Api\v1\AdminReviews::by_user');
    $routes->options('submit-review', static function () {
        return service('response')->setStatusCode(200);
    });























    // $routes->post('login-action', 'Api\v1\Home::login_action');
    // // $routes->post('user-registration', 'Api\v1\Home::user_registration');

    //================== Admin Portal Auth ==============================
    $routes->post('admin-login-action', 'Api\v1\AdminAuth::login_action');
    $routes->options('admin-login-action', static function () {
        return service('response')->setStatusCode(200);
    });

    //================== Admin Portal Data ==============================
    // All admin data endpoints require a logged-in admin session. They are
    // called from the admin panel via same-origin fetch, so the HttpOnly
    // session cookie travels automatically. admin-login-action stays public.
    $routes->group('', ['filter' => 'admin_api_auth'], static function ($routes) {
        $routes->get('admin-users-list',    'Api\v1\AdminData::users_list');
        $routes->get('admin-dashboard-stats','Api\v1\AdminDashboard::stats');
        $routes->get('admin-analytics-stats','Api\v1\AdminAnalytics::stats');
        $routes->get('admin-analytics-top-walkers','Api\v1\AdminAnalytics::topWalkers');
        $routes->get('admin-user-detail',          'Api\v1\AdminUserDetail::stats');

        $routes->get('admin-settings-get',           'Api\v1\AdminSettings::get');
        $routes->post('admin-settings-update-profile','Api\v1\AdminSettings::updateProfile');
        $routes->post('admin-settings-change-password','Api\v1\AdminSettings::changePassword');
        $routes->post('admin-settings-update-twilio','Api\v1\AdminSettings::updateTwilio');
        $routes->get('admin-notifications-stats','Api\v1\AdminNotifications::stats');
        $routes->post('admin-notifications-send', 'Api\v1\AdminNotifications::send');
        $routes->get('admin-transactions-stats',  'Api\v1\AdminTransactions::stats');
        $routes->get('admin-referrals-stats',     'Api\v1\AdminReferrals::stats');
        $routes->get('admin-causes-stats',        'Api\v1\AdminCauses::stats');
        $routes->post('admin-causes-save',        'Api\v1\AdminCauses::save');
        $routes->post('admin-causes-delete',      'Api\v1\AdminCauses::remove');
        $routes->get('admin-rewards-stats',        'Api\v1\AdminRewards::stats');
        $routes->post('admin-rewards-save-daily',  'Api\v1\AdminRewards::saveDaily');
        $routes->post('admin-rewards-save-signup', 'Api\v1\AdminRewards::saveSignup');
        $routes->post('admin-rewards-save-referral','Api\v1\AdminRewards::saveReferral');
    });



    // $routes->get('get-all-member-card', 'Api\v1\User::get_all_member_card');
    // $routes->post('add-edit-member-card', 'Api\v1\User::add_edit_member_card');
    // $routes->get('get-all-user', 'Api\v1\User::get_all_user');
    // $routes->post('add-edit-user', 'Api\v1\User::add_edit_user');


    // $routes->get('get-all-referral-code', 'Api\v1\User::get_all_referral_code');

    // $routes->get('get-all-lottery-winners', 'Api\v1\User::get_all_lottery_winners');
    // $routes->post('add-lottery-date', 'Api\v1\User::add_lottery_date');
    // $routes->post('announce-lottery-winners', 'Api\v1\User::announce_lottery_winners');


    // $routes->post('add-member-card', 'Api\v1\Member_card::add_member_card');
    // $routes->post('add-multiple-member-card', 'Api\v1\Member_card::add_multiple_member_card');
    // $routes->post('del-data', 'Api\v1\Del::del_data');
    // $routes->get('full-user-data', 'Api\v1\Member_card::full_user_data');
    
    // $routes->post('edit-user', 'Api\v1\Member_card::edit_user');
    // $routes->get('user-referral-data', 'Api\v1\Member_card::user_referral_data');
    // $routes->get('user-lottery-data', 'Api\v1\Member_card::user_lottery_data');

    // $routes->get('settings-details', 'Api\v1\Member_card::settings_details');
    // $routes->post('add-settings', 'Api\v1\Member_card::add_settings');
    // $routes->post('add-monthly-lottery', 'Api\v1\Member_card::add_monthly_lottery');

    // $routes->post('add-admin-settings', 'Api\v1\Member_card::add_admin_settings');
    // $routes->get('admin-settings-list', 'Api\v1\Member_card::admin_settings_list');
    
    // $routes->get('announce-random', 'Api\v1\Member_card::announce_random');

    // $routes->get('dashboard-list', 'Api\v1\Member_card::dashboard_list');
    // $routes->post('change-password', 'Api\v1\Member_card::change_password');


    // $routes->get('get-all-club', 'Api\v1\Club::get_all_club');
    
    // //============================Mobile API========================================
    // $routes->post('otp-send', 'Api\v1\Auth::sendOtp');
    // $routes->post('verify-otp', 'Api\v1\Auth::verifyOtp');
    

    // $routes->post('verify-login', 'Api\v1\Auth::verifyLogin');
    // $routes->post('verify-phone-number', 'Api\v1\Auth::verifyPhoneNumber');
    // $routes->post('change-password-mobile', 'Api\v1\Auth::changePassword');






    // $routes->get('check-member-card-status', 'Api\v1\User::check_member_card_status');
    // $routes->post('add-new-user-by-member-card', 'Api\v1\User::add_new_user_by_member_card');
    // $routes->post('add-new-user-by-refferal', 'Api\v1\User::add_new_user_by_refferal');
    
    // $routes->post('edit-user-profile', 'Api\v1\User::edit_user_profile');
    // $routes->get('generate-refferal-code', 'Api\v1\Points::generate_refferal_code');
    // $routes->get('member-wise-point-transaction', 'Api\v1\Points::member_wise_point_transaction');


    // $routes->get('refferal-code-list-with-status', 'Api\v1\Club::refferal_code_list_with_status');
    // $routes->get('winners-club-users', 'Api\v1\Club::winners_club_users');
    // $routes->post('join-club-user', 'Api\v1\Club::join_club_user');
    // $routes->get('get-latest-club', 'Api\v1\Club::get_latest_club');



    // $routes->post('cotact-us-form', 'Api\v1\Contact::contact_us_form');

   
});

?>
