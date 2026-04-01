<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class UserController extends Controller
{
    public function sign_in()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.sign_in');
    }
    public function sign_in_do(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email|max:255',
            'password' => 'required',
        ]);

        $credentials = [
            'email' => $request->email,
            'password' => $request->password,
        ];
        try {
            if (Auth::attempt($credentials)) {
                return redirect()->route('dashboard');
                // if (Role::where('name') == 'admin') {
                //     RateLimiter::clear($this->throttleKey());
                //     return redirect()->route('dashboard');
                //     // if (auth()->user()->user_type == 'admin') {
                //     //     $request->session()->regenerate();
                //     //     return redirect()->route('dashboard');
                //     // }
                // } else {
                //     RateLimiter::hit($this->throttleKey(), $seconds = 3600);
                //     Auth::logout();
                //     return redirect()->route('login');
                // }
            } else {
                // RateLimiter::hit($this->throttleKey(), $seconds = 3600);
                return redirect()->back()->with('warning', 'Invalid Credentials');
            }
        } catch (\Throwable $th) {
            return redirect()->back()->withErrors(['error' => $th->getMessage()]);
        }
    }

    public function sign_up()
    {
        return view('auth.sign_up');
    }
    public function sign_up_do(Request $request)
    {
        $user = new User();
        $user->user_name = $request->user_name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->save();
        return redirect()->route('dashboard')->with('success', 'User register sucessfull.');
    }
    public function throttleKey()
    {
        return request()->ip();
    }
    public function logout()
    {
        Auth::logout();
        return redirect()->route('sign_in');
    }
    public function dashboard()
    {
        if (Auth::check() && Auth::user()) {
            $property_count = 0;
            $host_count = 0;
            $property_count = Property::count();
            $host_count =  User::where('role', '!=', 'admin')->count();
            return view('main.dashboard', compact('property_count', 'host_count'));
        } else {
            return redirect()->route('sign_in');
        }
    }
}
