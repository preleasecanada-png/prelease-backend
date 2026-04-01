<?php

namespace App\Http\Controllers\FrontEndApi;

use App\Http\Controllers\Controller;
use App\Models\LeaseAgreement;
use App\Models\MaintenanceRequest;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Property;
use App\Models\RentalApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function stats()
    {
        try {
            $user = Auth::user();
            $data = [];

            if ($user->role === 'host') {
                // Landlord stats
                $data['total_properties'] = Property::where('user_id', $user->id)->count();
                $data['active_leases'] = LeaseAgreement::where('landlord_id', $user->id)
                    ->where('status', 'active')->count();
                $data['pending_applications'] = RentalApplication::whereHas('property', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })->where('status', 'pending')->count();
                $data['total_revenue'] = Payment::where('landlord_id', $user->id)
                    ->where('status', 'confirmed')->sum('amount');
                $data['open_maintenance'] = MaintenanceRequest::where('landlord_id', $user->id)
                    ->whereIn('status', ['pending', 'in_progress'])->count();
                $data['unread_notifications'] = Notification::where('user_id', $user->id)
                    ->where('is_read', false)->count();

                // Recent activity
                $data['recent_applications'] = RentalApplication::with(['user', 'property'])
                    ->whereHas('property', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })
                    ->orderBy('created_at', 'desc')
                    ->limit(5)->get();

            } else {
                // Renter stats
                $data['active_leases'] = LeaseAgreement::where('renter_id', $user->id)
                    ->where('status', 'active')->count();
                $data['my_applications'] = RentalApplication::where('user_id', $user->id)->count();
                $data['pending_applications'] = RentalApplication::where('user_id', $user->id)
                    ->where('status', 'pending')->count();
                $data['total_paid'] = Payment::where('renter_id', $user->id)
                    ->where('status', 'confirmed')->sum('amount');
                $data['open_maintenance'] = MaintenanceRequest::where('tenant_id', $user->id)
                    ->whereIn('status', ['pending', 'in_progress'])->count();
                $data['unread_notifications'] = Notification::where('user_id', $user->id)
                    ->where('is_read', false)->count();

                // Recent activity
                $data['recent_applications'] = RentalApplication::with(['property'])
                    ->where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)->get();
            }

            $data['role'] = $user->role;

            return response()->json(['status' => 200, 'data' => $data]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }
}
