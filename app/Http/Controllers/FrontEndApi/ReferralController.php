<?php

namespace App\Http\Controllers\FrontEndApi;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
                    'data' => ['referral_code' => $existing->referral_code]
                ]);
            }

            $code = 'PRL-' . strtoupper(Str::random(6));

            $referral = Referral::create([
                'referrer_id' => $user->id,
                'referral_code' => $code,
                'status' => 'pending',
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Referral code generated!',
                'data' => ['referral_code' => $referral->referral_code]
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
                'pending' => $referrals->whereIn('status', ['pending', 'registered'])->count(),
                'total_earned' => $referrals->where('remuneration_paid', true)->sum('remuneration_amount'),
            ];

            return response()->json([
                'status' => 200,
                'data' => ['referrals' => $referrals, 'stats' => $stats]
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }
}
