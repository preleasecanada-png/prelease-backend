<?php

namespace App\Http\Controllers\FrontEndApi;

use App\Http\Controllers\Controller;
use App\Models\LeaseAgreement;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentConfirmationNotification;
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
            $role = $request->role ?? 'renter';

            if ($role === 'landlord') {
                $payments = Payment::with(['property.propertyImages', 'renter', 'leaseAgreement'])
                    ->where('landlord_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
            } else {
                $payments = Payment::with(['property.propertyImages', 'landlord', 'leaseAgreement'])
                    ->where('renter_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
            }

            return response()->json(['status' => 200, 'data' => $payments]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::guard('api')->user();
            $payment = Payment::with(['property.propertyImages', 'renter', 'landlord', 'leaseAgreement'])
                ->where(function ($q) use ($user) {
                    $q->where('renter_id', $user->id)->orWhere('landlord_id', $user->id);
                })
                ->findOrFail($id);

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
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = Auth::guard('api')->user();
            $lease = LeaseAgreement::where('renter_id', $user->id)
                ->where('status', 'active')
                ->findOrFail($request->lease_agreement_id);

            $existingPayment = Payment::where('lease_agreement_id', $lease->id)
                ->whereIn('status', ['completed', 'processing'])
                ->first();

            if ($existingPayment) {
                return response()->json([
                    'status' => 409,
                    'message' => 'A payment already exists for this lease.'
                ]);
            }

            $months = $lease->lease_type === '3_month' ? 3 : 6;
            $rentAmount = $lease->total_rent;
            $supportFee = 100.00 * $months;
            $commissionFee = $rentAmount * 0.05;
            $insuranceFee = $lease->insurance_fee;
            $totalAmount = $rentAmount + $supportFee + $commissionFee + $insuranceFee;

            $payment = Payment::create([
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
                'payment_method' => $request->payment_method,
                'status' => 'pending',
                'landlord_payout_status' => 'pending',
                'landlord_payout_amount' => $rentAmount,
                'insurance_payout_status' => $insuranceFee > 0 ? 'pending' : 'not_applicable',
                'insurance_payout_amount' => $insuranceFee > 0 ? $insuranceFee : null,
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Payment initiated successfully!',
                'data' => $payment->load(['leaseAgreement', 'property'])
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
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

            if ($payment->lease_agreement_id) {
                $lease = LeaseAgreement::find($payment->lease_agreement_id);
                if ($lease) {
                    $booking = $lease->booking;
                    if ($booking) {
                        $booking->status = 'paid';
                        $booking->save();
                    }
                }
            }

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
            $lease = LeaseAgreement::where(function ($q) use ($user) {
                $q->where('renter_id', $user->id)->orWhere('landlord_id', $user->id);
            })->findOrFail($leaseId);

            $months = $lease->lease_type === '3_month' ? 3 : 6;

            return response()->json([
                'status' => 200,
                'data' => [
                    'lease_type' => $lease->lease_type,
                    'months' => $months,
                    'monthly_rent' => $lease->monthly_rent,
                    'total_rent' => $lease->total_rent,
                    'support_fee_per_month' => 100.00,
                    'total_support_fee' => 100.00 * $months,
                    'commission_fee' => $lease->commission_fee,
                    'insurance_fee' => $lease->insurance_fee,
                    'total_payable' => $lease->total_payable,
                ]
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }
}
