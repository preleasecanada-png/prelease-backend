<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\LeaseAgreement;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    public function index(Request $request)
    {
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

        return view('admin.payments.index', compact('payments', 'stats'));
    }

    public function show($id)
    {
        $payment = Payment::with(['renter', 'landlord', 'property', 'leaseAgreement'])->findOrFail($id);
        return view('admin.payments.show', compact('payment'));
    }

    public function processLandlordPayout(Request $request, $id)
    {
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

        return redirect()->back()->with('success', 'Landlord payout processed successfully.');
    }

    public function processInsurancePayout(Request $request, $id)
    {
        $payment = Payment::where('status', 'completed')
            ->where('insurance_payout_status', 'pending')
            ->findOrFail($id);

        $payment->insurance_payout_status = 'completed';
        $payment->save();

        return redirect()->back()->with('success', 'Insurance payout processed successfully.');
    }

    public function dashboard()
    {
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

        return view('admin.payments.dashboard', compact('stats', 'recentPayments'));
    }
}
