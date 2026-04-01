<?php

namespace App\Http\Controllers\FrontEndApi;

use App\Http\Controllers\Controller;
use App\Models\RentalInsurance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RentalInsuranceController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::guard('api')->user();

            $insurances = RentalInsurance::with(['leaseAgreement.property.propertyImages', 'leaseAgreement.landlord'])
                ->where('renter_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json(['status' => 200, 'data' => $insurances]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::guard('api')->user();

            $insurance = RentalInsurance::with(['leaseAgreement.property.propertyImages', 'leaseAgreement.landlord', 'leaseAgreement.renter'])
                ->where('renter_id', $user->id)
                ->findOrFail($id);

            return response()->json(['status' => 200, 'data' => $insurance]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function summary()
    {
        try {
            $user = Auth::guard('api')->user();

            $total = RentalInsurance::where('renter_id', $user->id)->count();
            $active = RentalInsurance::where('renter_id', $user->id)->where('status', 'active')->count();
            $pending = RentalInsurance::where('renter_id', $user->id)->where('status', 'pending')->count();
            $expired = RentalInsurance::where('renter_id', $user->id)->where('status', 'expired')->count();
            $totalPremium = RentalInsurance::where('renter_id', $user->id)
                ->whereIn('status', ['active', 'pending'])
                ->sum('premium_amount');

            return response()->json([
                'status' => 200,
                'data' => [
                    'total' => $total,
                    'active' => $active,
                    'pending' => $pending,
                    'expired' => $expired,
                    'total_premium' => round($totalPremium, 2),
                ]
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }
}
