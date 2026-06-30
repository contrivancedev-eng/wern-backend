<?php

namespace App\Controllers\Web;

use App\Controllers\Web\WebController;

class Home extends WebController
{
    public function home()                      { return view('Web/pages/home'); }
    public function user_onboarding()           { return view('Web/pages/user_onboarding'); }
    public function feature_daily_referrals()   { return view('Web/pages/feature_daily_referrals'); }
    public function feature_daily_social_map()  { return view('Web/pages/feature_daily_social_map'); }
    public function proximity_based_social()    { return view('Web/pages/proximity_based_social'); }
    public function step_based_rewards()        { return view('Web/pages/step_based_rewards'); }
    public function launching_soon()            { return view('Web/pages/launching_soon'); }
    public function privacy_policy()            { return view('Web/pages/privacy_policy'); }
    public function terms_of_service()          { return view('Web/pages/terms_of_service'); }
    public function delete_account()            { return view('Web/pages/delete_account'); }
}
