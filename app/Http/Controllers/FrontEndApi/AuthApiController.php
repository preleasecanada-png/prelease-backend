<?php

namespace App\Http\Controllers\FrontEndApi;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\UserVerificationEmail;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Amenties;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Google\Client as GoogleClient;

class AuthApiController extends Controller
{
    protected function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'date_of_birth' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'confirm_password' => 'required|same:password|min:8',
            'role' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['errors' => $errors], 422);
        }
        try {
            $user = new User();
            $user->user_name = $request->first_name . ' ' . $request->last_name;
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            $user->date_of_birth = $request->date_of_birth;
            $user->password = Hash::make($request->password);
            $user->role = $request->role;
            // $user->save();
            $user->verified = Str::random(42);
            $user->save();
            try {
                $verify_token = $user->verified;
                $verificationUrl = route('user.verify_email', $verify_token);
                try {
                    Mail::to($user->email)->send(new UserVerificationEmail($verificationUrl, $user));
                    Log::info($verificationUrl);
                } catch (\Throwable $th) {
                    Log::error($th->getMessage());
                }
            } catch (\Throwable $th) {
                return response()->json(['error' => $th->getMessage()], 422);
            }
            return response([
                'user' => $user,
                // 'token' => $token,
                'message' => 'You have been successfully register!.'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 422);
        }
    }
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['errors' => $errors], 422);
        }
        $credentials = [
            'email' => $request->email,
            'password' => $request->password,
        ];
        try {
            if (Auth::attempt($credentials)) {
                $user = Auth::user();
                $token = $user->createToken('Prelease Token')->accessToken;
                return response(['user' => $user, 'token' => $token, 'message' => 'You have successfully logged in.', 'user_id' => $user->id], 200);
            } else {
                return response()->json(['error' => 'Invalid credentials'], 422);
            }
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function verify($token)
    {
        $user = User::where('verified', $token)->firstOrFail();
        $user->email_verified_at = now();
        $user->verified = null;
        $user->verify_status = 1;
        $user->save();
        return redirect(config('app.frontend_url'));
    }

    public function logout(Request $request)
    {
        try {
            $email = $request->email;
            if (!$email) {
                return response(['error' => 'Email not found!']);
            }
            $user = User::where('email', $request->email)->first();
            if ($user) {
                $user->tokens->each(function ($token, $key) {
                    $token->delete();
                });;
                return response()->json(['message' => 'You have been successfully logged out!']);
            } else {
                return response(['error' => 'User does not exist'], 422);
            }
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 422);
        }
    }

    public function redirectToGoogle()
    {
        return response()->json([
            'url' => Socialite::driver('google')
                ->stateless()
                ->redirect()
                ->getTargetUrl(),
        ]);
    }
    public function googleCallback()
    {
        try {
            $socialiteUser = Socialite::driver('google')->stateless()->user();
        } catch (ClientException $e) {
            return response()->json(['error' => 'Invalid credentials provided.'], 422);
        }
        try {
            $user = User::query()
                ->firstOrCreate(
                    [
                        'email' => $socialiteUser->getEmail(),
                    ],
                    [
                        'email_verified_at' => now(),
                        'user_name' => $socialiteUser->getName(),
                        'google_id' => $socialiteUser->getId(),
                    ]
                );
            if ($user) {
                return redirect(config('app.frontend_url'));
            }
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return redirect(config('app.frontend_url') . '?error=' . urlencode($th->getMessage()));
        }
    }

    public function token_save(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        if ($user) {
            $token = $user->createToken('API Token')->accessToken;
            Log::info($token);
        } else {
            return response()->json(['error' => 'Invalid credentials.'], 422);
        }
        return response()->json(['user' => $user, 'token' => $token], 200);
    }
    protected function amenities()
    {
        try {
            $amenities = Amenties::orderBy('id', 'asc')->get();
            return response()->json(['status' => 200, 'data' => $amenities]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 404, 'error' => $th->getMessage()]);
        }
    }

    protected function profile_update(Request $request)
    {
        $id = $request->id;
        $user = User::find($id);
        if (!empty($request->first_name) && $request->first_name != '') {
            $user->first_name = $request->first_name;
        }
        if (!empty($request->last_name) && $request->last_name != '') {
            $user->last_name = $request->last_name;
        }
        if (!empty($request->bio) && $request->bio != '') {
            $user->bio = $request->bio;
        }
        if (!empty($request->gender) && $request->gender != '') {
            $user->gender = $request->gender;
        }
        if (!empty($request->date_of_birth) && $request->date_of_birth != '') {
            $user->date_of_birth = $request->date_of_birth;
        }
        if (!empty($request->phone_no) && $request->phone_no != '') {
            $user->phone_no = $request->phone_no;
        }
        if ($request->hasFile('picture')) {
            $file = $request->file('picture');
            $extension = $file->getClientOriginalExtension();
            $fileName = 'images/profile_pictures/' . time() . '_' . $user->id . '.' . $extension;
            $file->move(public_path('images/profile_pictures'), basename($fileName));
            $user->picture = $fileName;
        }
        $user->update();
        return response()->json(['status' => 200, 'message' => 'User profile updated successfully!', 'host' => $user]);
    }
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Email does not exist',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $user = User::where('email', $request->email)->first();

            $token = Str::random(64);

            $user->forgot_password_token  = $token;
            $user->forgot_password_expiry = Carbon::now()->addMinutes(30);
            $user->save();

            $resetLink = env('FRONTEND_URL') . '/reset-password?token=' . $token;

            Mail::send('emails.forgot-password', [
                'user' => $user,
                'resetLink' => $resetLink
            ], function ($message) use ($user) {
                $message->to($user->email);
                $message->subject('Reset Your Password');
            });

            return response()->json([
                'success' => true,
                'message' => 'Password reset link has been sent to your email.'
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send password reset link',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token'            => 'required',
            'password'         => 'required|min:8',
            'confirm_password' => 'required|same:password',
        ], [
            'token.required' => 'Reset token is missing',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'confirm_password.required' => 'Confirm password is required',
            'confirm_password.same' => 'Passwords do not match',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $user = User::where('forgot_password_token', $request->token)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid reset token'
                ], 400);
            }

            if (Carbon::now()->greaterThan($user->forgot_password_expiry)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reset token has expired'
                ], 400);
            }

            $user->password = Hash::make($request->password);

            $user->forgot_password_token  = null;
            $user->forgot_password_expiry = null;

            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully'
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error'   => $th->getMessage()
            ], 500);
        }
    }

    public function googleLogin(Request $request)
    {
        $client = new GoogleClient([
            'client_id' => env('GOOGLE_CLIENT_ID')
        ]);

        $payload = $client->verifyIdToken($request->token);

        if (!$payload) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        $googleId = $payload['sub'];
        $email    = $payload['email'];
        $name     = $payload['name'];

        $user = User::where('google_id', $googleId)->first();

        if (!$user) {
            $user = User::create([
                'first_name' => $name,
                'email'      => $email,
                'google_id'  => $googleId,
                'password'   => bcrypt(Str::random(16)),
            ]);
        }

        // 🔥 Passport token generate
        $token = $user->createToken('GoogleAuth')->accessToken;

        return response()->json([
            'user'  => $user,
            'token' => $token
        ]);
    }
}
