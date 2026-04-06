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

            $application->load(['property', 'renter', 'landlord']);
            $property = $application->property;
            $renterUser = $application->renter;
            $landlordUser = $application->landlord ?? $user;
            $province = $property->state ?? $property->city ?? 'Ontario';

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
                'terms' => $this->generateProvincialLease(
                    $province, $leaseType, $monthlyRent, $startDate, $endDate,
                    $property, $renterUser, $landlordUser, $request->special_conditions
                ),
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

    private function generateProvincialLease($province, $leaseType, $monthlyRent, $startDate, $endDate, $property, $renter, $landlord, $specialConditions = null)
    {
        $months = $leaseType === '3_month' ? 3 : 6;
        $totalRent = $monthlyRent * $months;
        $supportFee = 100 * $months;
        $commission = $totalRent * 0.05;
        $estInsurance = $totalRent * 0.02;
        $totalPayable = $totalRent + $supportFee + $commission + $estInsurance;

        $provincialRef = $this->getProvincialReference($province);

        $doc = "═══════════════════════════════════════════════════════════════\n";
        $doc .= "                  PRELEASE CANADA — RESIDENTIAL LEASE AGREEMENT\n";
        $doc .= "═══════════════════════════════════════════════════════════════\n\n";

        $doc .= "Governed by: {$provincialRef['act']}\n";
        $doc .= "Province: {$provincialRef['name']}\n";
        $doc .= "Date of Agreement: " . now()->format('F d, Y') . "\n\n";

        $doc .= "─── PARTIES ───────────────────────────────────────────────────\n\n";
        $doc .= "LANDLORD:\n";
        $doc .= "  Name: " . ($landlord->first_name ?? '') . " " . ($landlord->last_name ?? '') . "\n";
        $doc .= "  Email: " . ($landlord->email ?? '') . "\n\n";

        $doc .= "TENANT:\n";
        $doc .= "  Name: " . ($renter->first_name ?? '') . " " . ($renter->last_name ?? '') . "\n";
        $doc .= "  Email: " . ($renter->email ?? '') . "\n\n";

        $doc .= "─── RENTAL PROPERTY ───────────────────────────────────────────\n\n";
        $doc .= "  Address: " . ($property->address ?? '') . "\n";
        $doc .= "  City: " . ($property->city ?? '') . ", " . ($property->state ?? '') . "\n";
        $doc .= "  Property Type: " . ($property->property_type ?? 'Residential') . "\n";
        $doc .= "  Bedrooms: " . ($property->no_of_bedrooms ?? 'N/A') . "  |  Bathrooms: " . ($property->no_of_bathrooms ?? 'N/A') . "\n\n";

        $doc .= "─── LEASE TERMS ───────────────────────────────────────────────\n\n";
        $doc .= "  Lease Duration: " . str_replace('_', ' ', $leaseType) . " (" . $months . " months)\n";
        $doc .= "  Start Date: " . $startDate->format('F d, Y') . "\n";
        $doc .= "  End Date: " . $endDate->format('F d, Y') . "\n\n";

        $doc .= "─── FINANCIAL TERMS ───────────────────────────────────────────\n\n";
        $doc .= "  Monthly Rent:           $" . number_format($monthlyRent, 2) . "\n";
        $doc .= "  Total Rent ({$months} mo):     $" . number_format($totalRent, 2) . "\n";
        $doc .= "  Support Fee (\$100/mo):  $" . number_format($supportFee, 2) . "\n";
        $doc .= "  Commission (5%):        $" . number_format($commission, 2) . "\n";
        $doc .= "  Est. Insurance (2%):    $" . number_format($estInsurance, 2) . "\n";
        $doc .= "  ────────────────────────────────\n";
        $doc .= "  TOTAL PAYABLE:          $" . number_format($totalPayable, 2) . "\n\n";
        $doc .= "  Payment Method: Full upfront payment via Prelease Canada platform\n\n";

        if ($specialConditions) {
            $doc .= "─── SPECIAL CONDITIONS ────────────────────────────────────────\n\n";
            $doc .= "  " . $specialConditions . "\n\n";
        }

        $doc .= "─── STANDARD TERMS & CONDITIONS ───────────────────────────────\n\n";
        $doc .= "1. PAYMENT: The full rent amount and all fees must be paid upfront\n";
        $doc .= "   through the Prelease Canada platform before the lease start date.\n\n";
        $doc .= "2. INSURANCE: Rental insurance is mandatory and will be arranged\n";
        $doc .= "   through Prelease Canada. The exact premium is confirmed at signing.\n\n";
        $doc .= "3. MAINTENANCE: The Landlord is responsible for major repairs and\n";
        $doc .= "   maintaining the property in a habitable condition. Tenant must\n";
        $doc .= "   report maintenance issues promptly via the platform.\n\n";
        $doc .= "4. QUIET ENJOYMENT: The Tenant has the right to reasonable quiet\n";
        $doc .= "   enjoyment of the premises during the lease term.\n\n";
        $doc .= "5. CONDITION OF PREMISES: The Tenant agrees to keep the premises\n";
        $doc .= "   in a clean and good condition, ordinary wear and tear excepted.\n\n";
        $doc .= "6. SUBLETTING: Subletting is not permitted without prior written\n";
        $doc .= "   consent from the Landlord.\n\n";
        $doc .= "7. TERMINATION: Early termination is subject to the terms of\n";
        $doc .= "   this agreement and applicable provincial legislation.\n\n";

        $doc .= "─── PROVINCIAL COMPLIANCE ─────────────────────────────────────\n\n";
        $doc .= $provincialRef['terms'] . "\n\n";

        $doc .= "─── ELECTRONIC SIGNATURES ─────────────────────────────────────\n\n";
        $doc .= "Both parties agree that electronic signatures via the Prelease\n";
        $doc .= "Canada platform constitute valid and binding signatures under\n";
        $doc .= "Canadian federal and provincial electronic commerce legislation.\n\n";

        $doc .= "Landlord Signature: ____________________  Date: __________\n";
        $doc .= "Tenant Signature:   ____________________  Date: __________\n\n";

        $doc .= "═══════════════════════════════════════════════════════════════\n";
        $doc .= "  Facilitated by Prelease Canada | prelease.ca\n";
        $doc .= "═══════════════════════════════════════════════════════════════\n";

        return $doc;
    }

    private function getProvincialReference($province)
    {
        $p = strtolower(trim($province));

        $map = [
            'ontario' => [
                'name' => 'Ontario',
                'act' => 'Residential Tenancies Act, 2006 (Ontario)',
                'terms' => "This lease is governed by the Ontario Residential Tenancies Act, 2006.\n"
                    . "- The Landlord and Tenant Board (LTB) resolves disputes.\n"
                    . "- Rent increases are limited to the annual guideline published by the Ontario government.\n"
                    . "- The landlord cannot require post-dated cheques or more than first and last month's rent as deposit.\n"
                    . "- Standard form lease (Ontario Form) provisions apply where not superseded.",
            ],
            'quebec' => [
                'name' => 'Quebec',
                'act' => 'Civil Code of Quebec, Articles 1851-2000',
                'terms' => "This lease is governed by the Civil Code of Quebec.\n"
                    . "- The Tribunal administratif du logement (TAL) resolves disputes.\n"
                    . "- Rent increases require proper notice and are subject to TAL guidelines.\n"
                    . "- The lease automatically renews unless proper notice is given.\n"
                    . "- Security deposits are NOT permitted under Quebec law.",
            ],
            'british columbia' => [
                'name' => 'British Columbia',
                'act' => 'Residential Tenancy Act (British Columbia)',
                'terms' => "This lease is governed by the BC Residential Tenancy Act.\n"
                    . "- The Residential Tenancy Branch (RTB) resolves disputes.\n"
                    . "- Maximum security deposit is half of one month's rent.\n"
                    . "- Rent increases are limited to the annual allowable percentage.\n"
                    . "- Condition inspection reports are required at move-in and move-out.",
            ],
            'alberta' => [
                'name' => 'Alberta',
                'act' => 'Residential Tenancies Act (Alberta)',
                'terms' => "This lease is governed by the Alberta Residential Tenancies Act.\n"
                    . "- The Residential Tenancy Dispute Resolution Service (RTDRS) resolves disputes.\n"
                    . "- Security deposit cannot exceed one month's rent.\n"
                    . "- Interest must be paid on security deposits annually.\n"
                    . "- Written notice requirements apply for lease termination.",
            ],
        ];

        foreach ($map as $key => $val) {
            if (str_contains($p, $key)) return $val;
        }

        return [
            'name' => $province,
            'act' => 'Applicable Provincial Residential Tenancy Legislation',
            'terms' => "This lease is governed by the applicable provincial residential\n"
                . "tenancy legislation. Both parties are encouraged to consult the\n"
                . "relevant provincial tenancy authority for specific rights and obligations.",
        ];
    }
}
