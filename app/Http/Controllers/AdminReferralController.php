<?php

namespace App\Http\Controllers;

use App\Models\Referral;
use Illuminate\Http\Request;

class AdminReferralController extends Controller
{
    public function index(Request $request)
    {
        $query = Referral::with(['referrer', 'referred']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $referrals = $query->orderBy('created_at', 'desc')->paginate(20);

        $stats = [
            'total_referrals' => Referral::count(),
            'completed' => Referral::where('status', 'completed')->count(),
            'pending' => Referral::whereIn('status', ['pending', 'registered'])->count(),
            'total_paid' => Referral::where('remuneration_paid', true)->sum('remuneration_amount'),
        ];

        return view('admin.referrals.index', compact('referrals', 'stats'));
    }

    public function markCompleted(Request $request, $id)
    {
        $request->validate([
            'remuneration_amount' => 'required|numeric|min:0',
        ]);

        $referral = Referral::where('status', 'registered')->findOrFail($id);
        $referral->status = 'completed';
        $referral->remuneration_amount = $request->remuneration_amount;
        $referral->completed_at = now();
        $referral->save();

        return redirect()->back()->with('success', 'Referral marked as completed.');
    }

    public function processPayment($id)
    {
        $referral = Referral::where('status', 'completed')
            ->where('remuneration_paid', false)
            ->findOrFail($id);

        $referral->remuneration_paid = true;
        $referral->save();

        return redirect()->back()->with('success', 'Referral payment processed.');
    }
}
