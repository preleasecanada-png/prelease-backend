<?php

namespace App\Http\Controllers\FrontEndApi;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReferralController extends Controller
{
    public function generateCode()
    {
        try {
            $user = Auth::guard('api')->user();

            $existing = Referral::where('referrer_id', $user->id)
                ->whereNull('referred_id')
                ->where('status', 'pending')
                ->first();

            if ($existing) {
                return response()->json([
                    'status' => 200,
                    'data' => [
                        'referral_code' => $existing->referral_code,
                        'referral_link' => config('app.frontend_url') . '/sign-up?ref=' . $existing->referral_code
                    ]
                ]);
            }

            $code = ReferralService::generateUniqueCode($user->id);

            $referral = Referral::create([
                'referrer_id' => $user->id,
                'referral_code' => $code,
                'status' => 'pending',
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Referral code generated!',
                'data' => [
                    'referral_code' => $referral->referral_code,
                    'referral_link' => config('app.frontend_url') . '/sign-up?ref=' . $referral->referral_code
                ]
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function applyCode(Request $request)
    {
        try {
            $user = Auth::guard('api')->user();
            $code = $request->referral_code;

            $referral = Referral::where('referral_code', $code)
                ->where('status', 'pending')
                ->whereNull('referred_id')
                ->first();

            if (!$referral) {
                return response()->json(['status' => 404, 'message' => 'Invalid or expired referral code.']);
            }

            if ($referral->referrer_id === $user->id) {
                return response()->json(['status' => 400, 'message' => 'You cannot use your own referral code.']);
            }

            $referral->referred_id = $user->id;
            $referral->status = 'registered';
            $referral->save();

            return response()->json([
                'status' => 200,
                'message' => 'Referral code applied successfully!'
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function myReferrals()
    {
        try {
            $user = Auth::guard('api')->user();

            $referrals = Referral::with('referred')
                ->where('referrer_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            $stats = [
                'total_referrals' => $referrals->count(),
                'completed' => $referrals->where('status', 'completed')->count(),
                'registered' => $referrals->where('status', 'registered')->count(),
                'pending' => $referrals->where('status', 'pending')->count(),
                'total_earned' => ReferralService::getTotalEarnings($user->id),
                'pending_referrals' => ReferralService::getPendingCount($user->id),
                'bonus_percentage' => 5,
            ];

            return response()->json([
                'status' => 200,
                'data' => [
                    'referrals' => $referrals,
                    'stats' => $stats,
                    'current_referral_code' => $referrals->where('status', 'pending')->first()?->referral_code
                ]
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }
}
