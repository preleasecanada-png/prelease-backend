<?php

namespace App\Http\Controllers\FrontEndApi;

use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Models\Property;
use App\Models\RentalApplication;
use App\Models\User;
use App\Notifications\ApplicationStatusNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RentalApplicationController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = Auth::guard('api')->user();
            
            // Determine role based on user's role in database, not request parameter
            $isLandlord = in_array(strtolower($user->role), ['landlord', 'host', 'admin']);

            if ($isLandlord) {
                // Landlord sees applications for their properties (via property ownership)
                $propertyIds = Property::where('user_id', $user->id)->pluck('id');
                
                $applications = RentalApplication::with(['property.propertyImages', 'renter', 'documents'])
                    ->whereIn('property_id', $propertyIds)
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
                    
                // Also ensure landlord_id is set correctly for future queries
                foreach ($applications->items() as $app) {
                    if ($app->landlord_id !== $user->id) {
                        $app->landlord_id = $user->id;
                        $app->saveQuietly();
                    }
                }
            } else {
                // Renter sees their own applications
                $applications = RentalApplication::with(['property.propertyImages', 'landlord', 'documents'])
                    ->where('renter_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
            }

            return response()->json(['status' => 200, 'data' => $applications]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
            'cover_letter' => 'nullable|string|max:2000',
            'employment_status' => 'required|string',
            'monthly_income' => 'required|numeric|min:0',
            'current_address' => 'required|string',
            'reason_for_moving' => 'nullable|string',
            'number_of_occupants' => 'required|integer|min:1',
            'has_pets' => 'nullable|boolean',
            'pet_details' => 'nullable|string',
            'desired_move_in' => 'required|date',
            'desired_lease_duration' => 'required|in:3_month,6_month',
            'reference_name_1' => 'nullable|string',
            'reference_phone_1' => 'nullable|string',
            'reference_email_1' => 'nullable|email',
            'reference_name_2' => 'nullable|string',
            'reference_phone_2' => 'nullable|string',
            'reference_email_2' => 'nullable|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = Auth::guard('api')->user();
            $property = Property::findOrFail($request->property_id);

            $existing = RentalApplication::where('property_id', $request->property_id)
                ->where('renter_id', $user->id)
                ->whereIn('status', ['draft', 'submitted', 'under_review'])
                ->first();

            if ($existing) {
                return response()->json([
                    'status' => 409,
                    'message' => 'You already have an active application for this property.'
                ]);
            }

            $application = RentalApplication::create([
                'property_id' => $request->property_id,
                'renter_id' => $user->id,
                'landlord_id' => $property->user_id,
                'cover_letter' => $request->cover_letter,
                'employment_status' => $request->employment_status,
                'monthly_income' => $request->monthly_income,
                'current_address' => $request->current_address,
                'reason_for_moving' => $request->reason_for_moving,
                'number_of_occupants' => $request->number_of_occupants,
                'has_pets' => $request->has_pets ?? false,
                'pet_details' => $request->pet_details,
                'desired_move_in' => $request->desired_move_in,
                'desired_lease_duration' => $request->desired_lease_duration,
                'reference_name_1' => $request->reference_name_1,
                'reference_phone_1' => $request->reference_phone_1,
                'reference_email_1' => $request->reference_email_1,
                'reference_name_2' => $request->reference_name_2,
                'reference_phone_2' => $request->reference_phone_2,
                'reference_email_2' => $request->reference_email_2,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            $application->load(['property', 'documents']);

            try {
                $landlord = User::find($property->user_id);
                if ($landlord) {
                    $landlord->notify(new ApplicationStatusNotification($application, 'landlord'));
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to send application notification: ' . $e->getMessage());
            }

            return response()->json([
                'status' => 200,
                'message' => 'Application submitted successfully!',
                'data' => $application
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::guard('api')->user();
            $application = RentalApplication::with(['property.propertyImages', 'renter', 'landlord', 'documents'])
                ->findOrFail($id);

            $viewerIsLandlord = false;
            if ($application->property && (int) $application->property->user_id === (int) $user->id) {
                $viewerIsLandlord = true;
            }

            $viewerCanSee = ((int) $application->renter_id === (int) $user->id)
                || ((int) $application->landlord_id === (int) $user->id)
                || $viewerIsLandlord;

            if (!$viewerCanSee) {
                return response()->json(['status' => 403, 'message' => 'Unauthorized'], 403);
            }

            if ($viewerIsLandlord && (int) $application->landlord_id !== (int) $user->id) {
                $application->landlord_id = $user->id;
                $application->saveQuietly();
            }

            return response()->json(['status' => 200, 'data' => $application, 'viewer_is_landlord' => $viewerIsLandlord]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function uploadDocument(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rental_application_id' => 'required|exists:rental_applications,id',
            'document_type' => 'required|in:proof_of_income,identification,reference_letter,credit_report,employment_letter,other',
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = Auth::guard('api')->user();
            $application = RentalApplication::where('renter_id', $user->id)
                ->findOrFail($request->rental_application_id);

            $file = $request->file('document');
            $extension = $file->getClientOriginalExtension();
            $fileName = 'documents/' . time() . '_' . rand(1000, 9999) . '.' . $extension;
            $file->move(public_path('documents'), $fileName);

            $document = ApplicationDocument::create([
                'rental_application_id' => $application->id,
                'user_id' => $user->id,
                'document_type' => $request->document_type,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $fileName,
                'file_extension' => $extension,
                'file_size' => $file->getSize(),
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Document uploaded successfully!',
                'data' => $document
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:under_review,approved,rejected',
            'landlord_notes' => 'nullable|string',
            'rejection_reason' => 'nullable|string|required_if:status,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = Auth::guard('api')->user();
            $application = RentalApplication::with('property')->findOrFail($id);

            $viewerIsLandlord = false;
            if ($application->property && (int) $application->property->user_id === (int) $user->id) {
                $viewerIsLandlord = true;
            }

            $viewerCanUpdate = ((int) $application->landlord_id === (int) $user->id) || $viewerIsLandlord;
            if (!$viewerCanUpdate) {
                return response()->json(['status' => 403, 'message' => 'Unauthorized'], 403);
            }

            if ($viewerIsLandlord && (int) $application->landlord_id !== (int) $user->id) {
                $application->landlord_id = $user->id;
            }

            $application->status = $request->status;
            $application->landlord_notes = $request->landlord_notes;
            $application->reviewed_at = now();

            if ($request->status === 'rejected') {
                $application->rejection_reason = $request->rejection_reason;
            }

            $application->save();
            $application->load(['renter', 'property']);

            try {
                $renter = User::find($application->renter_id);
                if ($renter) {
                    $renter->notify(new ApplicationStatusNotification($application, 'renter'));
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to send application status notification: ' . $e->getMessage());
            }

            return response()->json([
                'status' => 200,
                'message' => 'Application status updated successfully!',
                'data' => $application
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function withdraw($id)
    {
        try {
            $user = Auth::guard('api')->user();
            $application = RentalApplication::where('renter_id', $user->id)
                ->whereIn('status', ['draft', 'submitted', 'under_review'])
                ->findOrFail($id);

            $application->status = 'withdrawn';
            $application->save();

            return response()->json([
                'status' => 200,
                'message' => 'Application withdrawn successfully!'
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }
}
