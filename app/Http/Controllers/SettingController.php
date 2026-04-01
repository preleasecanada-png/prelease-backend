<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Rules\MatchOldPassword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        return view('main.setting.index', compact('user'));
    }

    public function profile_update(Request $request)
    {
        $user = User::find($request->id);
        if (!$user) {
            return redirect()->back()->with(['error' => "Invalid request."]);
        }
        try {
            $user->user_name = $request->name;
            $user->email = $request->email;
            $user->phone_no = $request->phone;
            if ($request->file('picture') != '') {
                $file_name = ImagePathName($request->file('picture'), 'images/profile_picture/');
                $user->picture = $file_name;
            }
            $user->save();
            return redirect()->route('setting.index')->with('success', 'Profile Updated Successfull!');
        } catch (\Throwable $th) {
            return redirect()->back()->with(['error' => $th->getMessage()]);
        }
    }
    public function password_update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => ['required', new MatchOldPassword],
            'new_password' => ['required'],
            'confirm_password' => ['same:new_password'],
        ]);
        if ($validator->fails()) {
            return redirect(route('setting'))->withErrors($validator)->withInput();
        }
        try {
            $user = User::where('id', $request->id)->first();
            if ($request->password != '') {
                $user->password = Hash::make($request->new_password);
            }
            $user->save();
            return redirect()->route('setting')->with('success', 'Password change successfully.');
        } catch (\Throwable $th) {
            return redirect()->back()->withErrors($th->getMessage());
        }
    }
}
