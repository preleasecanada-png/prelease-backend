<?php

namespace App\Http\Controllers\FrontEndApi;

use App\Http\Controllers\Controller;
use App\Models\LeaseAgreement;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentConfirmationNotification;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = Auth::guard('api')->user();
            $isAdmin = strtolower($user->role ?? '') === 'admin';

            $query = Payment::with(['property.propertyImages', 'renter', 'landlord', 'leaseAgreement']);
            if (!$isAdmin) {
                $query->where(function ($q) use ($user) {
                    $q->where('renter_id', $user->id)->orWhere('landlord_id', $user->id);
                });
            }
            $payments = $query->orderBy('created_at', 'desc')->paginate(20);

            return response()->json(['status' => 200, 'data' => $payments]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::guard('api')->user();
            $isAdmin = strtolower($user->role ?? '') === 'admin';
            $query = Payment::with(['property.propertyImages', 'renter', 'landlord', 'leaseAgreement']);
            if (!$isAdmin) {
                $query->where(function ($q) use ($user) {
                    $q->where('renter_id', $user->id)->orWhere('landlord_id', $user->id);
                });
            }
            $payment = $query->findOrFail($id);

            return response()->json(['status' => 200, 'data' => $payment]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function initiatePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lease_agreement_id' => 'required|exists:lease_agreements,id',
            'payment_method' => 'required|in:credit_card,debit_card,bank_transfer,e_transfer',
            'payment_plan' => 'sometimes|in:full_upfront,monthly',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = Auth::guard('api')->user();
            // Allow payment when lease is active OR pending landlord signature (renter has already signed)
            $lease = LeaseAgreement::where('renter_id', $user->id)
                ->whereIn('status', ['active', 'pending_landlord_signature'])
                ->findOrFail($request->lease_agreement_id);

            $existingPayment = Payment::where('lease_agreement_id', $lease->id)
                ->whereIn('status', ['completed', 'processing', 'pending'])
                ->first();

            if ($existingPayment) {
                return response()->json([
                    'status' => 409,
                    'message' => 'Payment(s) already exist for this lease.'
                ]);
            }

            $plan = $request->payment_plan ?? 'full_upfront';
            $months = $lease->lease_type === '3_month' ? 3 : 6;

            // Monthly plan requires landlord approval
            if ($plan === 'monthly' && !$lease->landlord_allows_monthly) {
                return response()->json([
                    'status' => 403,
                    'message' => 'The landlord has not enabled monthly payments for this lease.'
                ]);
            }

            // Save chosen plan on lease
            $lease->payment_plan = $plan;
            $lease->save();

            if ($plan === 'full_upfront') {
                $payment = $this->createFullUpfrontPayment($lease, $user, $request->payment_method, $months);
                return response()->json([
                    'status' => 200,
                    'message' => 'Payment initiated successfully!',
                    'data' => $payment->load(['leaseAgreement', 'property']),
                ]);
            }

            // Monthly installments
            $installments = $this->createMonthlyInstallments($lease, $user, $request->payment_method, $months);
            return response()->json([
                'status' => 200,
                'message' => "Monthly payment plan created — {$months} installments.",
                'data' => $installments,
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    private function createFullUpfrontPayment($lease, $user, $paymentMethod, $months)
    {
        $rentAmount = $lease->total_rent;
        $supportFee = 100.00 * $months;
        $commissionFee = $rentAmount * 0.05;
        $insuranceFee = $lease->insurance_fee;
        $totalAmount = $rentAmount + $supportFee + $commissionFee + $insuranceFee;

        return Payment::create([
            'payment_reference' => 'PRL-' . strtoupper(Str::random(10)),
            'lease_agreement_id' => $lease->id,
            'renter_id' => $user->id,
            'landlord_id' => $lease->landlord_id,
            'property_id' => $lease->property_id,
            'rent_amount' => $rentAmount,
            'support_fee' => $supportFee,
            'commission_fee' => $commissionFee,
            'insurance_fee' => $insuranceFee,
            'total_amount' => $totalAmount,
            'payment_type' => 'full_upfront',
            'payment_method' => $paymentMethod,
            'status' => 'pending',
            'installment_number' => 1,
            'total_installments' => 1,
            'due_date' => now()->toDateString(),
            'installment_group' => 'PRL-GRP-' . strtoupper(Str::random(8)),
            'landlord_payout_status' => 'pending',
            'landlord_payout_amount' => $rentAmount,
            'insurance_payout_status' => $insuranceFee > 0 ? 'pending' : 'not_applicable',
            'insurance_payout_amount' => $insuranceFee > 0 ? $insuranceFee : null,
        ]);
    }

    private function createMonthlyInstallments($lease, $user, $paymentMethod, $months)
    {
        $monthlyRent = (float)$lease->monthly_rent;
        // Monthly plan: higher support fee ($125/mo) and commission (7%) to offset risk
        $monthlySupportFee = 125.00;
        $monthlyCommission = $monthlyRent * 0.07;
        $insuranceFee = (float)$lease->insurance_fee;
        $insurancePerMonth = $months > 0 ? round($insuranceFee / $months, 2) : 0;

        $groupId = 'PRL-GRP-' . strtoupper(Str::random(8));
        $startDate = $lease->start_date ?? now();
        $installments = [];

        for ($i = 1; $i <= $months; $i++) {
            $dueDate = \Carbon\Carbon::parse($startDate)->addMonths($i - 1);
            $monthlyTotal = $monthlyRent + $monthlySupportFee + $monthlyCommission + $insurancePerMonth;
            $landlordPayout = $monthlyRent; // landlord gets full rent each month

            $payment = Payment::create([
                'payment_reference' => 'PRL-' . strtoupper(Str::random(10)),
                'lease_agreement_id' => $lease->id,
                'renter_id' => $user->id,
                'landlord_id' => $lease->landlord_id,
                'property_id' => $lease->property_id,
                'rent_amount' => $monthlyRent,
                'support_fee' => $monthlySupportFee,
                'commission_fee' => $monthlyCommission,
                'insurance_fee' => $insurancePerMonth,
                'total_amount' => round($monthlyTotal, 2),
                'payment_type' => 'monthly',
                'payment_method' => $paymentMethod,
                'status' => $i === 1 ? 'pending' : 'pending',
                'installment_number' => $i,
                'total_installments' => $months,
                'due_date' => $dueDate->toDateString(),
                'installment_group' => $groupId,
                'landlord_payout_status' => 'pending',
                'landlord_payout_amount' => $landlordPayout,
                'insurance_payout_status' => $insurancePerMonth > 0 ? 'pending' : 'not_applicable',
                'insurance_payout_amount' => $insurancePerMonth > 0 ? $insurancePerMonth : null,
            ]);

            $installments[] = $payment;
        }

        return $installments;
    }

    public function confirmPayment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = Auth::guard('api')->user();
            $payment = Payment::where('renter_id', $user->id)
                ->where('status', 'pending')
                ->findOrFail($id);

            $payment->transaction_id = $request->transaction_id;
            $payment->status = 'completed';
            $payment->paid_at = now();
            $payment->save();

            // Process referral bonus (5% of total amount) for the renter's referrer
            try {
                ReferralService::processReferralBonus(
                    $payment->renter_id,
                    $payment->total_amount
                );
            } catch (\Throwable $e) {
                Log::warning('Referral bonus processing failed: ' . $e->getMessage());
                // Don't fail the payment if referral processing fails
            }

            // Note: lease.booking relationship is unused since leases are created from rental_applications,
            // not bookings. Booking status update is intentionally omitted.

            $payment->load(['leaseAgreement', 'property', 'renter', 'landlord']);

            try {
                $renter = User::find($payment->renter_id);
                $landlord = User::find($payment->landlord_id);
                if ($renter) {
                    $renter->notify(new PaymentConfirmationNotification($payment, 'renter'));
                }
                if ($landlord) {
                    $landlord->notify(new PaymentConfirmationNotification($payment, 'landlord'));
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to send payment notification: ' . $e->getMessage());
            }

            return response()->json([
                'status' => 200,
                'message' => 'Payment confirmed successfully!',
                'data' => $payment
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function breakdown($leaseId)
    {
        try {
            $user = Auth::guard('api')->user();
            $isAdmin = strtolower($user->role ?? '') === 'admin';
            $query = LeaseAgreement::query();
            if (!$isAdmin) {
                $query->where(function ($q) use ($user) {
                    $q->where('renter_id', $user->id)->orWhere('landlord_id', $user->id);
                });
            }
            $lease = $query->findOrFail($leaseId);

            $months = $lease->lease_type === '3_month' ? 3 : 6;
            $monthlyRent = (float)$lease->monthly_rent;
            $totalRent = $monthlyRent * $months;
            $insuranceFee = (float)$lease->insurance_fee;

            // Full upfront plan
            $upfrontSupport = 100.00 * $months;
            $upfrontCommission = $totalRent * 0.05;
            $upfrontTotal = $totalRent + $upfrontSupport + $upfrontCommission + $insuranceFee;

            // Monthly plan (higher fees)
            $monthlySupport = 125.00;
            $monthlyCommission = $monthlyRent * 0.07;
            $monthlyInsurance = $months > 0 ? round($insuranceFee / $months, 2) : 0;
            $monthlyInstallment = $monthlyRent + $monthlySupport + $monthlyCommission + $monthlyInsurance;
            $monthlyPlanTotal = round($monthlyInstallment * $months, 2);
            $extraCost = round($monthlyPlanTotal - $upfrontTotal, 2);

            return response()->json([
                'status' => 200,
                'data' => [
                    'lease_type' => $lease->lease_type,
                    'months' => $months,
                    'monthly_rent' => $monthlyRent,
                    'total_rent' => $totalRent,
                    'insurance_fee' => $insuranceFee,
                    'landlord_allows_monthly' => (bool)$lease->landlord_allows_monthly,
                    'current_plan' => $lease->payment_plan ?? 'full_upfront',
                    'plans' => [
                        'full_upfront' => [
                            'label' => 'Pay in Full',
                            'support_fee_per_month' => 100.00,
                            'total_support_fee' => $upfrontSupport,
                            'commission_rate' => '5%',
                            'commission_fee' => $upfrontCommission,
                            'total_payable' => $upfrontTotal,
                            'savings' => $extraCost > 0 ? $extraCost : 0,
                        ],
                        'monthly' => [
                            'label' => 'Monthly Installments',
                            'enabled' => (bool)$lease->landlord_allows_monthly,
                            'installments' => $months,
                            'monthly_rent' => $monthlyRent,
                            'support_fee_per_month' => $monthlySupport,
                            'commission_rate' => '7%',
                            'commission_per_month' => round($monthlyCommission, 2),
                            'insurance_per_month' => $monthlyInsurance,
                            'installment_amount' => round($monthlyInstallment, 2),
                            'total_payable' => $monthlyPlanTotal,
                            'extra_cost' => $extraCost,
                        ],
                    ],
                ],
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    /**
     * Allow landlord to toggle monthly payment option on a lease
     */
    public function toggleMonthlyPayment(Request $request, $leaseId)
    {
        try {
            $user = Auth::guard('api')->user();
            $lease = LeaseAgreement::where('landlord_id', $user->id)->findOrFail($leaseId);

            $lease->landlord_allows_monthly = !$lease->landlord_allows_monthly;
            $lease->save();

            return response()->json([
                'status' => 200,
                'message' => $lease->landlord_allows_monthly
                    ? 'Monthly payments enabled for this lease.'
                    : 'Monthly payments disabled for this lease.',
                'landlord_allows_monthly' => $lease->landlord_allows_monthly,
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }
}
