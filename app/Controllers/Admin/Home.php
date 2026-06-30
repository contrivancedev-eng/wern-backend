<?php

namespace App\Controllers\Admin;

use App\Controllers\Admin\AdminController;

class Home extends AdminController
{
    public function index()      { return view('Admin/pages/dashboard'); }
    public function login()
    {
        // Already authenticated → skip the login screen.
        if (session()->get('authID')) {
            return redirect()->to('/admin/dashboard');
        }
        return view('Admin/login');
    }
    public function logout()
    {
        session()->destroy();
        return redirect()->to('/admin/login');
    }
    public function dashboard()  { return view('Admin/pages/dashboard'); }
    public function users()      { return view('Admin/pages/users'); }
    public function analytics()  { return view('Admin/pages/analytics'); }
    public function notifications() { return view('Admin/pages/notifications'); }
    public function reviews()       { return view('Admin/pages/reviews'); }
    public function review_view()   { return view('Admin/pages/review_view'); }
    public function transactions()  { return view('Admin/pages/transactions'); }
    public function referrals()  { return view('Admin/pages/referrals'); }
    public function causes()     { return view('Admin/pages/causes'); }
    public function rewards()    { return view('Admin/pages/rewards'); }
    public function content()    { return view('Admin/pages/content'); }
    public function settings()   { return view('Admin/pages/settings'); }
    public function user_detail(){ return view('Admin/pages/user_detail'); }
}
