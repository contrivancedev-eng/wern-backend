<?php

$routes->group('admin', static function ($routes) {
    // Public (no session required).
    $routes->get('/',             'Admin\Home::login');
    $routes->get('login',         'Admin\Home::login');
    $routes->get('logout',        'Admin\Home::logout');

    // Everything below requires a logged-in admin session. The login_check
    // filter redirects to /admin/login when 'authID' is not set.
    $routes->group('', ['filter' => 'login_check'], static function ($routes) {
        $routes->get('dashboard',     'Admin\Home::dashboard');
        $routes->get('users',         'Admin\Home::users');
        $routes->get('analytics',     'Admin\Home::analytics');
        $routes->get('notifications', 'Admin\Home::notifications');
        $routes->get('reviews',       'Admin\Home::reviews');
        $routes->get('review-view',   'Admin\Home::review_view');
        $routes->get('transactions',  'Admin\Home::transactions');
        $routes->get('referrals',     'Admin\Home::referrals');
        $routes->get('causes',        'Admin\Home::causes');
        $routes->get('rewards',       'Admin\Home::rewards');
        $routes->get('content',       'Admin\Home::content');
        $routes->get('settings',      'Admin\Home::settings');
        $routes->get('user-detail',   'Admin\Home::user_detail');
    });
});
