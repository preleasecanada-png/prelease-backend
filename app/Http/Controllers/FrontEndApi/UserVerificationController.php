<?php

namespace App\Http\Controllers\FrontEndApi;

use App\Http\Controllers\Controller;
use App\Models\UserVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserVerificationController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::guard('api')->user();
            $verifications = UserVerification::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['status' => 200, 'data' => $verifications]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function submit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'verification_type' => 'required|in:identity,income,address,landlord_ownership',
            'document_type' => 'required|string',
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = Auth::guard('api')->user();

            $existing = UserVerification::where('user_id', $user->id)
                ->where('verification_type', $request->verification_type)
                ->whereIn('status', ['pending', 'under_review', 'verified'])
                ->first();

            if ($existing && $existing->status === 'verified') {
                return response()->json([
                    'status' => 409,
                    'message' => 'This verification type is already verified.'
                ]);
            }

            $file = $request->file('document');
            $extension = $file->getClientOriginalExtension();
            $fileName = 'verifications/' . time() . '_' . rand(1000, 9999) . '.' . $extension;
            $file->move(public_path('verifications'), $fileName);

            if ($existing) {
                $existing->document_type = $request->document_type;
                $existing->document_path = $fileName;
                $existing->status = 'pending';
                $existing->admin_notes = null;
                $existing->save();
                $verification = $existing;
            } else {
                $verification = UserVerification::create([
                    'user_id' => $user->id,
                    'verification_type' => $request->verification_type,
                    'document_type' => $request->document_type,
                    'document_path' => $fileName,
                    'status' => 'pending',
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Verification document submitted successfully!',
                'data' => $verification
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function status()
    {
        try {
            $user = Auth::guard('api')->user();
            $verifications = UserVerification::where('user_id', $user->id)->get();

            $types = ['identity', 'income', 'address', 'landlord_ownership'];
            $status = [];
            foreach ($types as $type) {
                $v = $verifications->where('verification_type', $type)->first();
                $status[$type] = $v ? $v->status : 'not_submitted';
            }

            $isFullyVerified = collect($status)->every(function ($s) use ($user) {
                if ($user->role === 'host') {
                    return in_array($s, ['verified']);
                }
                return in_array($s, ['verified', 'not_submitted']);
            });

            return response()->json([
                'status' => 200,
                'data' => [
                    'verifications' => $status,
                    'is_fully_verified' => $isFullyVerified,
                ]
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }
}
