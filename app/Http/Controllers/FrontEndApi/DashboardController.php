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

            // Determine role based on user's role in database, not request parameter
            $isLandlord = in_array(strtolower($user->role), ['landlord', 'host', 'admin']);

            if ($isLandlord) {
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

    public function landlordReport()
    {
        try {
            $user = Auth::user();
            $isLandlord = in_array(strtolower($user->role), ['landlord', 'host', 'admin']);
            if (!$isLandlord) {
                return response()->json(['status' => 403, 'message' => 'Unauthorized'], 403);
            }

            $propertyIds = Property::where('user_id', $user->id)->pluck('id');

            // Property performance
            $properties = Property::where('user_id', $user->id)
                ->withCount([
                    'bookings',
                    'bookings as active_bookings_count' => function ($q) {
                        $q->whereIn('status', ['pending', 'payment_pending', 'paid']);
                    },
                ])
                ->get(['id', 'title', 'city', 'set_your_price', 'created_at']);

            foreach ($properties as $prop) {
                $prop->total_earned = Payment::where('landlord_id', $user->id)
                    ->where('property_id', $prop->id)
                    ->where('status', 'paid')
                    ->sum('landlord_payout_amount');
                $prop->app_count = RentalApplication::where('property_id', $prop->id)->count();
                $reviews = \App\Models\Review::where('property_id', $prop->id);
                $prop->avg_rating = $reviews->count() > 0 ? round($reviews->avg('overall_rating'), 1) : 0;
                $prop->review_count = $reviews->count();
            }

            // Monthly revenue (last 6 months)
            $monthlyRevenue = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $rev = Payment::where('landlord_id', $user->id)
                    ->where('status', 'paid')
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('landlord_payout_amount');
                $monthlyRevenue[] = [
                    'month' => $date->format('M Y'),
                    'amount' => round((float)$rev, 2),
                ];
            }

            // Application funnel
            $appTotal = RentalApplication::whereIn('property_id', $propertyIds)->count();
            $appApproved = RentalApplication::whereIn('property_id', $propertyIds)->where('status', 'approved')->count();
            $appRejected = RentalApplication::whereIn('property_id', $propertyIds)->where('status', 'rejected')->count();
            $appPending = RentalApplication::whereIn('property_id', $propertyIds)->where('status', 'pending')->count();

            // Maintenance summary
            $maintTotal = MaintenanceRequest::where('landlord_id', $user->id)->count();
            $maintOpen = MaintenanceRequest::where('landlord_id', $user->id)->whereIn('status', ['pending', 'in_progress'])->count();
            $maintResolved = MaintenanceRequest::where('landlord_id', $user->id)->where('status', 'completed')->count();

            // Totals
            $totalEarned = Payment::where('landlord_id', $user->id)->where('status', 'paid')->sum('landlord_payout_amount');
            $totalLeases = LeaseAgreement::where('landlord_id', $user->id)->count();
            $activeLeases = LeaseAgreement::where('landlord_id', $user->id)->where('status', 'active')->count();

            return response()->json([
                'status' => 200,
                'data' => [
                    'properties' => $properties,
                    'monthly_revenue' => $monthlyRevenue,
                    'application_funnel' => [
                        'total' => $appTotal,
                        'approved' => $appApproved,
                        'rejected' => $appRejected,
                        'pending' => $appPending,
                    ],
                    'maintenance' => [
                        'total' => $maintTotal,
                        'open' => $maintOpen,
                        'resolved' => $maintResolved,
                    ],
                    'total_earned' => round((float)$totalEarned, 2),
                    'total_leases' => $totalLeases,
                    'active_leases' => $activeLeases,
                ],
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }
}
