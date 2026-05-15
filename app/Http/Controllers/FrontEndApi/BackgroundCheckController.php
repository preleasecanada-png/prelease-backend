<?php

namespace App\Http\Controllers\FrontEndApi;

use App\Http\Controllers\Controller;
use App\Models\BackgroundCheck;
use App\Models\Notification;
use App\Models\RentalApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BackgroundCheckController extends Controller
{
    /**
     * List background checks for the authenticated user
     */
    public function index()
    {
        try {
            $user = Auth::guard('api')->user();

            if ($user->role === 'host') {
                $checks = BackgroundCheck::with(['renter:id,first_name,last_name,email', 'rentalApplication.property:id,title,city'])
                    ->where('landlord_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->get();
            } else {
                $checks = BackgroundCheck::with(['landlord:id,first_name,last_name', 'rentalApplication.property:id,title,city'])
                    ->where('renter_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->get();
            }

            return response()->json(['status' => 200, 'data' => $checks]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    /**
     * Landlord requests a background check for a rental application
     */
    public function request(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rental_application_id' => 'required|exists:rental_applications,id',
            'check_type' => 'required|in:credit,criminal,both',
            'fee_paid_by' => 'sometimes|in:renter,landlord',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        try {
            $user = Auth::guard('api')->user();
            if ($user->role !== 'host') {
                return response()->json(['status' => 403, 'message' => 'Only landlords can request background checks'], 403);
            }

            $application = RentalApplication::with('property')
                ->where('landlord_id', $user->id)
                ->findOrFail($request->rental_application_id);

            // Check if already requested
            $existing = BackgroundCheck::where('rental_application_id', $application->id)
                ->whereNotIn('status', ['failed', 'declined'])
                ->first();

            if ($existing) {
                return response()->json(['status' => 409, 'message' => 'A background check already exists for this application', 'data' => $existing]);
            }

            $check = BackgroundCheck::create([
                'rental_application_id' => $application->id,
                'renter_id' => $application->renter_id,
                'landlord_id' => $user->id,
                'check_type' => $request->check_type,
                'status' => 'pending_consent',
                'fee_paid_by' => $request->fee_paid_by ?? 'renter',
                'fee_amount' => $request->check_type === 'both' ? 45.00 : 25.00,
            ]);

            // Notify the renter
            Notification::create([
                'user_id' => $application->renter_id,
                'title' => 'Background Check Requested',
                'message' => "The landlord for \"{$application->property->title}\" has requested a {$request->check_type} check. Please provide your consent.",
                'type' => 'background_check',
                'link' => '/applications',
                'is_read' => false,
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Background check requested. Waiting for tenant consent.',
                'data' => $check,
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    /**
     * Renter gives or declines consent for a background check
     */
    public function consent(Request $request, $id)
    {
        try {
            $user = Auth::guard('api')->user();
            $check = BackgroundCheck::where('renter_id', $user->id)
                ->where('status', 'pending_consent')
                ->findOrFail($id);

            $action = $request->input('action', 'approve'); // approve or decline

            if ($action === 'decline') {
                $check->update([
                    'status' => 'declined',
                    'notes' => 'Tenant declined the background check.',
                ]);

                Notification::create([
                    'user_id' => $check->landlord_id,
                    'title' => 'Background Check Declined',
                    'message' => "The tenant has declined the {$check->check_type} check for application #{$check->rental_application_id}.",
                    'type' => 'background_check',
                    'link' => '/applications',
                    'is_read' => false,
                ]);

                return response()->json(['status' => 200, 'message' => 'Background check declined.', 'data' => $check]);
            }

            $check->update([
                'renter_consent' => true,
                'consent_given_at' => now(),
                'status' => 'in_progress',
            ]);

            // Simulate processing (in production, this would call a third-party API like Certn or Equifax)
            $this->simulateBackgroundCheck($check);

            return response()->json([
                'status' => 200,
                'message' => 'Consent given. Background check is being processed.',
                'data' => $check->fresh(),
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    /**
     * Get a single background check result
     */
    public function show($id)
    {
        try {
            $user = Auth::guard('api')->user();
            $check = BackgroundCheck::with(['renter:id,first_name,last_name,email', 'rentalApplication.property:id,title,city'])
                ->where(function ($q) use ($user) {
                    $q->where('landlord_id', $user->id)->orWhere('renter_id', $user->id);
                })
                ->findOrFail($id);

            return response()->json(['status' => 200, 'data' => $check]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    /**
     * Simulate a background check result (placeholder for real API integration)
     */
    private function simulateBackgroundCheck(BackgroundCheck $check)
    {
        $creditScore = rand(550, 850);
        $rating = $creditScore >= 750 ? 'excellent' : ($creditScore >= 670 ? 'good' : ($creditScore >= 580 ? 'fair' : 'poor'));

        $updates = [
            'status' => 'completed',
            'completed_at' => now(),
        ];

        if (in_array($check->check_type, ['credit', 'both'])) {
            $updates['credit_score'] = $creditScore;
            $updates['credit_rating'] = $rating;
            $updates['credit_summary'] = "Credit score: {$creditScore}. Rating: {$rating}. "
                . "Payment history: Good standing. "
                . "Outstanding debts: \$" . number_format(rand(0, 15000), 2) . ". "
                . "Credit utilization: " . rand(10, 60) . "%.";
        }

        if (in_array($check->check_type, ['criminal', 'both'])) {
            $updates['criminal_result'] = 'clear';
            $updates['criminal_summary'] = 'No criminal records found in Canadian police databases.';
        }

        $check->update($updates);

        // Notify landlord
        Notification::create([
            'user_id' => $check->landlord_id,
            'title' => 'Background Check Completed',
            'message' => "The {$check->check_type} check for application #{$check->rental_application_id} is complete. Credit rating: {$rating}.",
            'type' => 'background_check',
            'link' => '/applications',
            'is_read' => false,
        ]);
    }
}
