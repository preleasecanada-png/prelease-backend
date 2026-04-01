<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardsController extends Controller
{
    public function index()
    {
        // return view('pages.index');
        return view('main.dashboard');
    }
    public function index2()
    {
        return view('pages.index2');
    }
    public function index3()
    {
        return view('pages.index3');
    }
    public function index4()
    {
        return view('pages.index4');
    }
    public function index5()
    {
        return view('pages.index5');
    }
    
}
