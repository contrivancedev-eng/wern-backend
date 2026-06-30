<?php

$routes->group('', static function ($routes) {
    $routes->get('/',                                  'Web\Home::home');
    $routes->get('home',                               'Web\Home::home');
    $routes->get('user-onboarding',                    'Web\Home::user_onboarding');
    $routes->get('feature-daily-referrals',            'Web\Home::feature_daily_referrals');
    $routes->get('feature-daily-social-map',           'Web\Home::feature_daily_social_map');
    $routes->get('proximity-based-social',             'Web\Home::proximity_based_social');
    $routes->get('step-based-rewards',                 'Web\Home::step_based_rewards');
    $routes->get('launching-soon',                     'Web\Home::launching_soon');
    $routes->get('privacy-and-data-protection-policy', 'Web\Home::privacy_policy');
    $routes->get('terms-of-service',                   'Web\Home::terms_of_service');
    $routes->get('delete-my-account',                     'Web\Home::delete_account');
});
