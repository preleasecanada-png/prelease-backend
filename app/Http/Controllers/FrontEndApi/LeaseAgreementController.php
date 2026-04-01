<?php

namespace App\Http\Controllers\FrontEndApi;

use App\Http\Controllers\Controller;
use App\Models\LeaseAgreement;
use App\Models\RentalApplication;
use App\Models\RentalInsurance;
use App\Models\User;
use App\Notifications\LeaseReminderNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LeaseAgreementController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = Auth::guard('api')->user();
            $role = $request->role ?? 'renter';

            if ($role === 'landlord') {
                $leases = LeaseAgreement::with(['property.propertyImages', 'renter', 'payments', 'insurance'])
                    ->where('landlord_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
            } else {
                $leases = LeaseAgreement::with(['property.propertyImages', 'landlord', 'payments', 'insurance'])
                    ->where('renter_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
            }

            return response()->json(['status' => 200, 'data' => $leases]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::guard('api')->user();
            $lease = LeaseAgreement::with(['property.propertyImages', 'renter', 'landlord', 'payments', 'insurance', 'rentalApplication'])
                ->where(function ($q) use ($user) {
                    $q->where('renter_id', $user->id)->orWhere('landlord_id', $user->id);
                })
                ->findOrFail($id);

            return response()->json(['status' => 200, 'data' => $lease]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function createFromApplication(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rental_application_id' => 'required|exists:rental_applications,id',
            'monthly_rent' => 'required|numeric|min:0',
            'special_conditions' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = Auth::guard('api')->user();
            $application = RentalApplication::where('landlord_id', $user->id)
                ->where('status', 'approved')
                ->findOrFail($request->rental_application_id);

            $leaseType = $application->desired_lease_duration;
            $months = $leaseType === '3_month' ? 3 : 6;
            $startDate = Carbon::parse($application->desired_move_in);
            $endDate = $startDate->copy()->addMonths($months);

            $monthlyRent = $request->monthly_rent;
            $totalRent = $monthlyRent * $months;
            $supportFee = 100.00 * $months;
            $commissionFee = $totalRent * 0.05;
            $insuranceFee = 0;
            $totalPayable = $totalRent + $supportFee + $commissionFee + $insuranceFee;

            $lease = LeaseAgreement::create([
                'property_id' => $application->property_id,
                'renter_id' => $application->renter_id,
                'landlord_id' => $application->landlord_id,
                'rental_application_id' => $application->id,
                'lease_type' => $leaseType,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'monthly_rent' => $monthlyRent,
                'total_rent' => $totalRent,
                'support_fee' => $supportFee,
                'commission_fee' => $commissionFee,
                'insurance_fee' => $insuranceFee,
                'total_payable' => $totalPayable,
                'status' => 'pending_renter_signature',
                'special_conditions' => $request->special_conditions,
                'terms' => $this->generateLeaseTerms($leaseType, $monthlyRent, $startDate, $endDate),
            ]);

            $lease->load(['property', 'renter', 'landlord']);

            try {
                $renter = User::find($lease->renter_id);
                if ($renter) {
                    $renter->notify(new LeaseReminderNotification($lease, 'renter', 'signing'));
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to send lease creation notification: ' . $e->getMessage());
            }

            return response()->json([
                'status' => 200,
                'message' => 'Lease agreement created successfully!',
                'data' => $lease
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function sign(Request $request, $id)
    {
        try {
            $user = Auth::guard('api')->user();
            $lease = LeaseAgreement::where(function ($q) use ($user) {
                $q->where('renter_id', $user->id)->orWhere('landlord_id', $user->id);
            })->findOrFail($id);

            if ($user->id === $lease->renter_id && $lease->status === 'pending_renter_signature') {
                $lease->renter_signed_at = now();
                $lease->status = 'pending_landlord_signature';
                $lease->save();

                try {
                    $landlord = User::find($lease->landlord_id);
                    if ($landlord) {
                        $landlord->notify(new LeaseReminderNotification($lease, 'landlord', 'signing'));
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to send lease signing notification: ' . $e->getMessage());
                }
            } elseif ($user->id === $lease->landlord_id && $lease->status === 'pending_landlord_signature') {
                $lease->landlord_signed_at = now();
                $lease->status = 'active';
                $lease->save();

                RentalInsurance::create([
                    'lease_agreement_id' => $lease->id,
                    'renter_id' => $lease->renter_id,
                    'premium_amount' => 0,
                    'coverage_start' => $lease->start_date,
                    'coverage_end' => $lease->end_date,
                    'status' => 'pending',
                    'coverage_details' => 'Mandatory rental insurance - pending provider assignment.',
                ]);

                try {
                    $renter = User::find($lease->renter_id);
                    $landlord = User::find($lease->landlord_id);
                    if ($renter) {
                        $renter->notify(new LeaseReminderNotification($lease, 'renter', 'active'));
                    }
                    if ($landlord) {
                        $landlord->notify(new LeaseReminderNotification($lease, 'landlord', 'active'));
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to send lease active notification: ' . $e->getMessage());
                }
            } else {
                return response()->json([
                    'status' => 400,
                    'message' => 'You cannot sign this lease at this stage.'
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Lease signed successfully!',
                'data' => $lease->fresh(['property', 'renter', 'landlord', 'insurance'])
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    public function terminate(Request $request, $id)
    {
        try {
            $user = Auth::guard('api')->user();
            $lease = LeaseAgreement::where(function ($q) use ($user) {
                $q->where('renter_id', $user->id)->orWhere('landlord_id', $user->id);
            })
                ->where('status', 'active')
                ->findOrFail($id);

            $lease->status = 'terminated';
            $lease->save();

            return response()->json([
                'status' => 200,
                'message' => 'Lease terminated.',
                'data' => $lease
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'error' => $th->getMessage()]);
        }
    }

    private function generateLeaseTerms($leaseType, $monthlyRent, $startDate, $endDate)
    {
        $months = $leaseType === '3_month' ? 3 : 6;
        $totalRent = $monthlyRent * $months;

        return "PRELEASE CANADA LEASE AGREEMENT\n\n"
            . "Lease Type: " . str_replace('_', ' ', $leaseType) . " lease\n"
            . "Monthly Rent: $" . number_format($monthlyRent, 2) . "\n"
            . "Total Rent: $" . number_format($totalRent, 2) . "\n"
            . "Lease Period: " . $startDate->format('M d, Y') . " to " . $endDate->format('M d, Y') . "\n"
            . "Payment: Complete upfront payment required\n"
            . "Support Fee: $100.00/month (Prelease Canada service fee)\n\n"
            . "TERMS AND CONDITIONS:\n"
            . "1. The full rent amount must be paid upfront before the lease start date.\n"
            . "2. Rental insurance is mandatory and will be arranged through Prelease Canada.\n"
            . "3. This lease is governed by the applicable provincial tenancy laws.\n"
            . "4. Both parties agree to the terms outlined in this agreement.\n";
    }
}
