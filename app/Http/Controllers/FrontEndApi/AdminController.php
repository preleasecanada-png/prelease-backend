<?php

namespace App\Http\Controllers\FrontEndApi;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Property;
use App\Models\PropertyImages;
use App\Models\RentalApplication;
use App\Models\LeaseAgreement;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    /**
     * Platform statistics for admin dashboard
     */
    public function stats()
    {
        try {
            $totalUsers = User::count();
            $totalHosts = User::where('role', 'host')->count();
            $totalRenters = User::where('role', 'renter')->count();
            $totalAdmins = User::where('role', 'admin')->count();
            $totalProperties = Property::count();
            $totalImages = PropertyImages::count();

            $recentUsers = User::orderBy('created_at', 'desc')->take(5)->get(['id', 'first_name', 'last_name', 'email', 'role', 'created_at']);
            $recentProperties = Property::with('user:id,first_name,last_name,email')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get(['id', 'title', 'city', 'state', 'set_your_price', 'user_id', 'created_at']);

            // Monthly user registrations (last 6 months)
            $monthlyUsers = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $count = User::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();
                $monthlyUsers[] = [
                    'month' => $date->format('M Y'),
                    'count' => $count,
                ];
            }

            // Advanced analytics
            $totalApplications = RentalApplication::count();
            $pendingApplications = RentalApplication::whereIn('status', ['submitted', 'under_review'])->count();
            $approvedApplications = RentalApplication::where('status', 'approved')->count();
            $rejectedApplications = RentalApplication::where('status', 'rejected')->count();

            $totalLeases = LeaseAgreement::count();
            $activeLeases = LeaseAgreement::where('status', 'active')->count();

            $totalPayments = Payment::count();
            $totalRevenue = Payment::where('status', 'completed')->sum('total_amount');
            $totalCommission = Payment::where('status', 'completed')->sum('commission_fee');

            $totalMaintenance = \App\Models\MaintenanceRequest::count();
            $openMaintenance = \App\Models\MaintenanceRequest::whereIn('status', ['pending', 'in_progress'])->count();

            // Monthly revenue (last 6 months)
            $monthlyRevenue = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $rev = Payment::where('status', 'completed')
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('total_amount');
                $monthlyRevenue[] = [
                    'month' => $date->format('M Y'),
                    'amount' => round((float)$rev, 2),
                ];
            }

            // Monthly listings (last 6 months)
            $monthlyListings = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $count = Property::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();
                $monthlyListings[] = [
                    'month' => $date->format('M Y'),
                    'count' => $count,
                ];
            }

            // Top cities by listings
            $topCities = Property::selectRaw('city, COUNT(*) as count')
                ->whereNotNull('city')
                ->where('city', '!=', '')
                ->groupBy('city')
                ->orderByDesc('count')
                ->take(5)
                ->get();

            return response()->json([
                'status' => 200,
                'data' => [
                    'total_users' => $totalUsers,
                    'total_hosts' => $totalHosts,
                    'total_renters' => $totalRenters,
                    'total_admins' => $totalAdmins,
                    'total_properties' => $totalProperties,
                    'total_images' => $totalImages,
                    'total_applications' => $totalApplications,
                    'pending_applications' => $pendingApplications,
                    'approved_applications' => $approvedApplications,
                    'rejected_applications' => $rejectedApplications,
                    'total_leases' => $totalLeases,
                    'active_leases' => $activeLeases,
                    'total_payments' => $totalPayments,
                    'total_revenue' => round((float)$totalRevenue, 2),
                    'total_commission' => round((float)$totalCommission, 2),
                    'total_maintenance' => $totalMaintenance,
                    'open_maintenance' => $openMaintenance,
                    'recent_users' => $recentUsers,
                    'recent_properties' => $recentProperties,
                    'monthly_users' => $monthlyUsers,
                    'monthly_revenue' => $monthlyRevenue,
                    'monthly_listings' => $monthlyListings,
                    'top_cities' => $topCities,
                ],
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    /**
     * List all users with filters
     */
    public function users(Request $request)
    {
        try {
            $query = User::query();

            if ($request->has('role') && $request->role !== 'all') {
                $query->where('role', $request->role);
            }

            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $users = $query->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 20);

            return response()->json([
                'status' => 200,
                'data' => $users,
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    /**
     * Get single user details with their properties
     */
    public function userDetail($id)
    {
        try {
            $user = User::findOrFail($id);
            $properties = Property::with('propertyImages')
                ->where('user_id', $id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 200,
                'data' => [
                    'user' => $user,
                    'properties' => $properties,
                    'property_count' => $properties->count(),
                ],
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    /**
     * Update user role
     */
    public function updateUserRole(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            if (!in_array($request->role, ['admin', 'host', 'renter'])) {
                return response()->json(['status' => 422, 'error' => 'Invalid role']);
            }

            $user->role = $request->role;
            $user->save();

            return response()->json([
                'status' => 200,
                'message' => 'User role updated successfully',
                'data' => $user,
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    /**
     * Delete a user
     */
    public function deleteUser($id)
    {
        try {
            $user = User::findOrFail($id);

            if ($user->id === Auth::id()) {
                return response()->json(['status' => 422, 'error' => 'Cannot delete yourself']);
            }

            // Delete user's property images and properties
            $properties = Property::where('user_id', $id)->get();
            foreach ($properties as $property) {
                PropertyImages::where('property_id', $property->id)->delete();
            }
            Property::where('user_id', $id)->delete();

            $user->delete();

            return response()->json([
                'status' => 200,
                'message' => 'User and associated data deleted successfully',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    /**
     * List all properties with filters (admin view)
     */
    public function properties(Request $request)
    {
        try {
            $query = Property::with(['propertyImages', 'user:id,first_name,last_name,email,role']);

            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('city', 'like', "%{$search}%")
                      ->orWhere('state', 'like', "%{$search}%");
                });
            }

            if ($request->has('city') && $request->city) {
                $query->where('city', $request->city);
            }

            $properties = $query->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 20);

            return response()->json([
                'status' => 200,
                'data' => $properties,
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    /**
     * Delete a property (admin)
     */
    public function deleteProperty($id)
    {
        try {
            $property = Property::findOrFail($id);
            PropertyImages::where('property_id', $id)->delete();
            $property->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Property deleted successfully',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }
}
