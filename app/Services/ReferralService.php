<?php

namespace App\Services;

use App\Models\Referral;
use App\Models\User;
use App\Notifications\ReferralBonusNotification;
use Illuminate\Support\Facades\Log;

class ReferralService
{
    /**
     * Process referral bonus when a user completes their first payment
     * 
     * @param int $referredUserId The user who made the payment (was referred)
     * @param float $paymentAmount The total payment amount
     * @return bool True if bonus was processed successfully
     */
    public static function processReferralBonus(int $referredUserId, float $paymentAmount): bool
    {
        try {
            // Find the referral record where this user is the referred user
            $referral = Referral::where('referred_id', $referredUserId)
                ->where('status', 'registered')
                ->where('remuneration_paid', false)
                ->first();

            if (!$referral) {
                Log::info('No pending referral found for user', ['user_id' => $referredUserId]);
                return false;
            }

            // Calculate 5% bonus
            $bonusAmount = round($paymentAmount * 0.05, 2);

            // Update referral record
            $referral->update([
                'status' => 'completed',
                'remuneration_amount' => $bonusAmount,
                'remuneration_paid' => true,
                'completed_at' => now(),
            ]);

            // Get referrer and referred user info
            $referrer = User::find($referral->referrer_id);
            $referredUser = User::find($referredUserId);

            if ($referrer && $referredUser) {
                // Send notification to referrer
                $referrer->notify(new ReferralBonusNotification(
                    $referral,
                    $bonusAmount,
                    $referredUser->first_name . ' ' . $referredUser->last_name
                ));

                Log::info('Referral bonus processed successfully', [
                    'referral_id' => $referral->id,
                    'referrer_id' => $referral->referrer_id,
                    'referred_id' => $referredUserId,
                    'bonus_amount' => $bonusAmount,
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to process referral bonus', [
                'user_id' => $referredUserId,
                'payment_amount' => $paymentAmount,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Generate a unique referral code for a user
     * 
     * @param int $userId
     * @return string
     */
    public static function generateUniqueCode(int $userId): string
    {
        $prefix = 'PRL';
        $attempts = 0;
        $maxAttempts = 10;

        do {
            $code = $prefix . '-' . strtoupper(\Illuminate\Support\Str::random(6));
            $exists = Referral::where('referral_code', $code)->exists();
            $attempts++;
        } while ($exists && $attempts < $maxAttempts);

        if ($attempts >= $maxAttempts) {
            // Fallback with timestamp
            $code = $prefix . '-' . strtoupper(\Illuminate\Support\Str::random(4)) . time();
        }

        return $code;
    }

    /**
     * Get total earnings for a referrer
     * 
     * @param int $userId
     * @return float
     */
    public static function getTotalEarnings(int $userId): float
    {
        return Referral::where('referrer_id', $userId)
            ->where('remuneration_paid', true)
            ->sum('remuneration_amount') ?? 0;
    }

    /**
     * Get pending referrals count for a referrer
     * 
     * @param int $userId
     * @return int
     */
    public static function getPendingCount(int $userId): int
    {
        return Referral::where('referrer_id', $userId)
            ->where('status', 'registered')
            ->where('remuneration_paid', false)
            ->count();
    }
}
