<?php

namespace App\Http\Controllers;

use App\Models\UserVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminVerificationController extends Controller
{
    public function index(Request $request)
    {
        $query = UserVerification::with('user');

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->type) {
            $query->where('verification_type', $request->type);
        }

        $verifications = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.verifications.index', compact('verifications'));
    }

    public function show($id)
    {
        $verification = UserVerification::with('user')->findOrFail($id);
        return view('admin.verifications.show', compact('verification'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:under_review,verified,rejected',
            'admin_notes' => 'nullable|string',
        ]);

        $verification = UserVerification::findOrFail($id);
        $verification->status = $request->status;
        $verification->admin_notes = $request->admin_notes;
        $verification->reviewed_by = Auth::id();
        $verification->reviewed_at = now();
        $verification->save();

        return redirect()->back()->with('success', 'Verification status updated successfully.');
    }
}
