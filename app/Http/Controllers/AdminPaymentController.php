<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\LeaseAgreement;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Payment::with(['renter', 'landlord', 'property', 'leaseAgreement']);

            if ($request->status) {
                $query->where('status', $request->status);
            }
            if ($request->payout_status) {
                $query->where('landlord_payout_status', $request->payout_status);
            }

            $payments = $query->orderBy('created_at', 'desc')->paginate(20);

            $stats = [
                'total_revenue' => Payment::where('status', 'completed')->sum('total_amount'),
                'total_support_fees' => Payment::where('status', 'completed')->sum('support_fee'),
                'total_commission' => Payment::where('status', 'completed')->sum('commission_fee'),
                'pending_payouts' => Payment::where('landlord_payout_status', 'pending')
                    ->where('status', 'completed')->sum('landlord_payout_amount'),
            ];

            return response()->json(['status' => 200, 'data' => $payments, 'stats' => $stats]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $payment = Payment::with(['renter', 'landlord', 'property', 'leaseAgreement'])->findOrFail($id);
            return response()->json(['status' => 200, 'data' => $payment]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()], 500);
        }
    }

    public function processLandlordPayout(Request $request, $id)
    {
        try {
            $request->validate([
                'notes' => 'nullable|string',
            ]);

            $payment = Payment::where('status', 'completed')
                ->where('landlord_payout_status', 'pending')
                ->findOrFail($id);

            $payment->landlord_payout_status = 'completed';
            $payment->landlord_paid_at = now();
            $payment->notes = $request->notes;
            $payment->save();

            return response()->json(['status' => 200, 'message' => 'Landlord payout processed successfully.', 'data' => $payment]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()], 500);
        }
    }

    public function processInsurancePayout(Request $request, $id)
    {
        try {
            $payment = Payment::where('status', 'completed')
                ->where('insurance_payout_status', 'pending')
                ->findOrFail($id);

            $payment->insurance_payout_status = 'completed';
            $payment->save();

            return response()->json(['status' => 200, 'message' => 'Insurance payout processed successfully.', 'data' => $payment]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()], 500);
        }
    }

    public function dashboard()
    {
        try {
            $stats = [
                'total_payments' => Payment::count(),
                'completed_payments' => Payment::where('status', 'completed')->count(),
                'pending_payments' => Payment::where('status', 'pending')->count(),
                'total_revenue' => Payment::where('status', 'completed')->sum('total_amount'),
                'prelease_earnings' => Payment::where('status', 'completed')
                    ->selectRaw('SUM(support_fee + commission_fee) as total')->value('total'),
                'pending_landlord_payouts' => Payment::where('status', 'completed')
                    ->where('landlord_payout_status', 'pending')->count(),
                'active_leases' => LeaseAgreement::where('status', 'active')->count(),
            ];

            $recentPayments = Payment::with(['renter', 'landlord', 'property'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json(['status' => 200, 'stats' => $stats, 'recent_payments' => $recentPayments]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()], 500);
        }
    }
}
