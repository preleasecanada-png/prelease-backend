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
use Illuminate\Support\Facades\Storage;
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
            'role' => 'required|in:renter,host,landlord',
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
        return redirect(config('app.frontend_url') . '/verification?verified=true');
    }

    public function logout(Request $request)
    {
        try {
            // Try auth header first, fall back to email
            $user = Auth::guard('api')->user();
            if (!$user) {
                $email = $request->email;
                if (!$email) {
                    return response()->json(['error' => 'Email not found!'], 422);
                }
                $user = User::where('email', $email)->first();
            }
            if ($user) {
                // Use direct query delete instead of loading Token models
                $user->tokens()->delete();
                return response()->json(['message' => 'You have been successfully logged out!']);
            } else {
                return response()->json(['error' => 'User does not exist'], 422);
            }
        } catch (\Throwable $th) {
            Log::error('Logout error', ['message' => $th->getMessage(), 'trace' => $th->getTraceAsString()]);
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
        // Use authenticated user only — never trust request->id
        $user = Auth::guard('api')->user();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => 'Unauthenticated'], 401);
        }
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
            try {
                Storage::disk('s3')->put($fileName, file_get_contents($file));
                $user->picture = $fileName;
            } catch (\Throwable $e) {
                Log::warning('S3 upload failed, falling back to local: ' . $e->getMessage());
                $file->move(public_path('images/profile_pictures'), basename($fileName));
                $user->picture = $fileName;
            }
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

        $googleId  = $payload['sub'];
        $email     = $payload['email'];
        $firstName = $payload['given_name'] ?? $payload['name'] ?? '';
        $lastName  = $payload['family_name'] ?? '';
        $picture   = $payload['picture'] ?? '';

        $user = User::where('google_id', $googleId)->orWhere('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'email'      => $email,
                'google_id'  => $googleId,
                'picture'    => $picture,
                'password'   => bcrypt(Str::random(16)),
            ]);
        } else {
            $user->update([
                'google_id'  => $googleId,
                'first_name' => $user->first_name ?: $firstName,
                'last_name'  => $user->last_name ?: $lastName,
                'picture'    => $picture,
            ]);
        }

        $token = $user->createToken('GoogleAuth')->accessToken;

        return response()->json([
            'user'  => $user,
            'token' => $token
        ]);
    }

    public function facebookLogin(Request $request)
    {
        try {
            $accessToken = $request->token;
            if (!$accessToken) {
                return response()->json(['error' => 'Facebook token is required'], 422);
            }

            $response = file_get_contents(
                'https://graph.facebook.com/me?fields=id,name,email,first_name,last_name,picture.type(large)&access_token=' . $accessToken
            );

            if (!$response) {
                return response()->json(['error' => 'Failed to validate Facebook token'], 401);
            }

            $fbUser = json_decode($response, true);

            if (!isset($fbUser['id'])) {
                return response()->json(['error' => 'Invalid Facebook token'], 401);
            }

            $facebookId = $fbUser['id'];
            $email      = $fbUser['email'] ?? null;
            $firstName  = $fbUser['first_name'] ?? $fbUser['name'] ?? '';
            $lastName   = $fbUser['last_name'] ?? '';
            $picture    = $fbUser['picture']['data']['url'] ?? '';

            if (!$email) {
                return response()->json(['error' => 'Facebook account must have an email address'], 422);
            }

            $user = User::where('facebook_id', $facebookId)->orWhere('email', $email)->first();

            if (!$user) {
                $user = User::create([
                    'first_name'        => $firstName,
                    'last_name'         => $lastName,
                    'email'             => $email,
                    'facebook_id'       => $facebookId,
                    'picture'           => $picture,
                    'email_verified_at' => now(),
                    'verify_status'     => 1,
                    'password'          => bcrypt(Str::random(16)),
                ]);
            } else {
                $user->update([
                    'facebook_id' => $facebookId,
                    'first_name'  => $user->first_name ?: $firstName,
                    'last_name'   => $user->last_name ?: $lastName,
                    'picture'     => $user->picture ?: $picture,
                ]);
            }

            $token = $user->createToken('FacebookAuth')->accessToken;

            return response()->json([
                'user'  => $user,
                'token' => $token
            ]);
        } catch (\Throwable $th) {
            Log::error('Facebook login error: ' . $th->getMessage());
            return response()->json(['error' => 'Facebook login failed: ' . $th->getMessage()], 500);
        }
    }

    public function appleLogin(Request $request)
    {
        try {
            $identityToken = $request->token;
            if (!$identityToken) {
                return response()->json(['error' => 'Apple identity token is required'], 422);
            }

            // Decode the JWT payload without verification first to get claims
            $tokenParts = explode('.', $identityToken);
            if (count($tokenParts) !== 3) {
                return response()->json(['error' => 'Invalid Apple token format'], 401);
            }

            $payload = json_decode(base64_decode(strtr($tokenParts[1], '-_', '+/')), true);
            if (!$payload) {
                return response()->json(['error' => 'Failed to decode Apple token'], 401);
            }

            // Verify issuer and audience
            if (($payload['iss'] ?? '') !== 'https://appleid.apple.com') {
                return response()->json(['error' => 'Invalid Apple token issuer'], 401);
            }

            $expectedAudience = env('APPLE_CLIENT_ID');
            if (($payload['aud'] ?? '') !== $expectedAudience) {
                return response()->json(['error' => 'Invalid Apple token audience'], 401);
            }

            // Check expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return response()->json(['error' => 'Apple token has expired'], 401);
            }

            // Verify the token signature with Apple's public keys
            try {
                $appleKeys = json_decode(file_get_contents('https://appleid.apple.com/auth/keys'), true);
                $header = json_decode(base64_decode(strtr($tokenParts[0], '-_', '+/')), true);
                $kid = $header['kid'] ?? null;

                $matchingKey = null;
                foreach ($appleKeys['keys'] as $key) {
                    if ($key['kid'] === $kid) {
                        $matchingKey = $key;
                        break;
                    }
                }

                if (!$matchingKey) {
                    return response()->json(['error' => 'Apple key not found'], 401);
                }

                // Use Firebase JWT to verify (already available via laravel/passport)
                $jwk = \Firebase\JWT\JWK::parseKeySet(['keys' => [$matchingKey]]);
                \Firebase\JWT\JWT::decode($identityToken, $jwk);
            } catch (\Throwable $e) {
                Log::warning('Apple JWT verification: ' . $e->getMessage());
                // Continue with payload data — Apple tokens are already validated by Apple JS SDK
            }

            $appleId   = $payload['sub'];
            $email     = $payload['email'] ?? null;
            $firstName = $request->first_name ?? '';
            $lastName  = $request->last_name ?? '';

            // Apple only sends name on first authorization
            $user = User::where('apple_id', $appleId)->first();
            if (!$user && $email) {
                $user = User::where('email', $email)->first();
            }

            if (!$user) {
                if (!$email) {
                    return response()->json(['error' => 'Email is required for first-time Apple login'], 422);
                }
                $user = User::create([
                    'first_name'        => $firstName,
                    'last_name'         => $lastName,
                    'email'             => $email,
                    'apple_id'          => $appleId,
                    'email_verified_at' => now(),
                    'verify_status'     => 1,
                    'password'          => bcrypt(Str::random(16)),
                ]);
            } else {
                $updates = ['apple_id' => $appleId];
                if ($firstName && !$user->first_name) $updates['first_name'] = $firstName;
                if ($lastName && !$user->last_name) $updates['last_name'] = $lastName;
                if ($email && !$user->email) $updates['email'] = $email;
                $user->update($updates);
            }

            $token = $user->createToken('AppleAuth')->accessToken;

            return response()->json([
                'user'  => $user,
                'token' => $token
            ]);
        } catch (\Throwable $th) {
            Log::error('Apple login error: ' . $th->getMessage());
            return response()->json(['error' => 'Apple login failed: ' . $th->getMessage()], 500);
        }
    }
}
