<?php

namespace App\Http\Controllers\FrontEndApi;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRequest;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MaintenanceRequestController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();
            $query = MaintenanceRequest::with(['tenant', 'landlord', 'property']);

            $isLandlord = in_array(strtolower($user->role), ['landlord', 'host', 'admin']);
            if ($isLandlord) {
                $query->where('landlord_id', $user->id);
            } else {
                $query->where('tenant_id', $user->id);
            }

            $requests = $query->orderBy('created_at', 'desc')->paginate(15);

            return response()->json(['status' => 200, 'data' => $requests]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'property_id' => 'required|exists:properties,id',
                'landlord_id' => 'required|exists:users,id',
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'category' => 'required|in:plumbing,electrical,appliance,structural,pest,general',
                'priority' => 'required|in:low,medium,high,urgent',
                'photos.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 422, 'errors' => $validator->errors()]);
            }

            $user = Auth::user();

            $photoPaths = [];
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $fileName = 'maintenance/' . uniqid() . '_' . time() . '.' . $photo->getClientOriginalExtension();
                    $photo->storeAs('', $fileName, 's3');
                    $photoPaths[] = $fileName;
                }
            }

            $maintenanceRequest = MaintenanceRequest::create([
                'tenant_id' => $user->id,
                'landlord_id' => $request->landlord_id,
                'property_id' => $request->property_id,
                'lease_id' => $request->lease_id,
                'title' => $request->title,
                'description' => $request->description,
                'category' => $request->category,
                'priority' => $request->priority,
                'photos' => count($photoPaths) > 0 ? $photoPaths : null,
            ]);

            // Notify landlord
            Notification::create([
                'user_id' => $request->landlord_id,
                'title' => 'New Maintenance Request',
                'message' => "Tenant {$user->first_name} submitted a {$request->priority} priority maintenance request: {$request->title}",
                'type' => 'maintenance',
                'link' => "/maintenance/{$maintenanceRequest->id}",
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Maintenance request submitted successfully',
                'data' => $maintenanceRequest->load(['tenant', 'landlord', 'property']),
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::user();
            $request = MaintenanceRequest::with(['tenant', 'landlord', 'property'])
                ->where(function ($q) use ($user) {
                    $q->where('tenant_id', $user->id)
                      ->orWhere('landlord_id', $user->id);
                })
                ->findOrFail($id);

            return response()->json(['status' => 200, 'data' => $request]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $maintenanceRequest = MaintenanceRequest::where('landlord_id', $user->id)->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,in_progress,completed,cancelled',
                'landlord_response' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 422, 'errors' => $validator->errors()]);
            }

            $maintenanceRequest->status = $request->status;
            if ($request->landlord_response) {
                $maintenanceRequest->landlord_response = $request->landlord_response;
            }
            if ($request->status === 'completed') {
                $maintenanceRequest->resolved_at = now();
            }
            $maintenanceRequest->save();

            // Notify tenant
            $statusLabel = ucfirst(str_replace('_', ' ', $request->status));
            Notification::create([
                'user_id' => $maintenanceRequest->tenant_id,
                'title' => "Maintenance Request {$statusLabel}",
                'message' => "Your maintenance request \"{$maintenanceRequest->title}\" has been updated to: {$statusLabel}",
                'type' => 'maintenance',
                'link' => "/maintenance/{$maintenanceRequest->id}",
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Maintenance request updated',
                'data' => $maintenanceRequest->load(['tenant', 'landlord', 'property']),
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }
}
