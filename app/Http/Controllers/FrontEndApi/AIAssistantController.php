<?php

namespace App\Http\Controllers\FrontEndApi;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\LeaseAgreement;
use App\Models\RentalApplication;
use App\Models\RentalInsurance;
use App\Models\RenterPreference;
use App\Models\Payment;
use App\Models\WishList;
use App\Models\MaintenanceRequest;
use App\Models\SupportTicket;
use App\Models\AiChatHistory;
use App\Models\Review;
use App\Models\User;
use App\Notifications\ApplicationStatusNotification;
use App\Notifications\LeaseReminderNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AIAssistantController extends Controller
{
    private function getSystemPrompt($user, $context)
    {
        $role = $user->role ?? 'user';
        $name = $user->first_name ?? 'User';
        $email = $user->email ?? '';

        $verified = $user->verify_status ?? 'pending';
        $phone    = $user->phone_no ?? 'N/A';

        $basePrompt = <<<PROMPT
You are Prelease AI, the intelligent assistant for the Prelease Canada rental platform. You have FULL ACCESS to real-time platform data. You can see all properties, applications, leases, payments, insurance, support tickets, and user data. USE THIS DATA to give specific, actionable answers.

CURRENT USER: {$name} ({$email}), Role: {$role} | Verification: {$verified} | Phone: {$phone}

PLATFORM OVERVIEW:
Prelease Canada connects landlords and tenants for short-term rentals (3 or 6 months) across Canada.

RENTAL PROCESS:
1. Tenant browses properties → submits application (cover letter, income, references)
2. Landlord reviews → approves or rejects
3. If approved → landlord creates a lease agreement (sets monthly rent)
4. Tenant signs → then landlord signs → lease becomes ACTIVE
5. Tenant makes full upfront payment → rental insurance auto-created
6. Lease statuses: pending_renter_signature → pending_landlord_signature → active → terminated

FEE STRUCTURE:
- Monthly rent × months = Total rent
- Support fee: \$100/month × months
- Commission: 5% of total rent
- Insurance: TBD
- Total payable = Rent + Support + Commission + Insurance

DIRECT LINKS — You MUST include clickable links when mentioning any page or property:
- Property detail: {$context['base_url']}/property-detail/{slug}/{id}
- All properties: {$context['base_url']}/property-lists
- My applications: {$context['base_url']}/applications
- My leases: {$context['base_url']}/leases
- My payments: {$context['base_url']}/payments
- Payment checkout: {$context['base_url']}/payment-checkout
- Wish list: {$context['base_url']}/wish-lists
- Preferences: {$context['base_url']}/preferences
- Find home: {$context['base_url']}/find-home
- Apply to property: {$context['base_url']}/apply?property_id={id}
- My properties (host): {$context['base_url']}/my-properties
- Create property (host): {$context['base_url']}/properties
- Notifications: {$context['base_url']}/notifications
- Insurance: {$context['base_url']}/insurance
- Maintenance: {$context['base_url']}/maintenance
- Support: {$context['base_url']}/support
- Reviews: {$context['base_url']}/reviews
- Account: {$context['base_url']}/account
- Verification: {$context['base_url']}/user-verification

FORMAT LINKS AS MARKDOWN: [Link text](url) — for example: [View property](https://dev.preleasecanada.ca/property-detail/my-apartment/5)

CRITICAL INSTRUCTIONS:
- You have REAL platform data below. Use it to answer specific questions about properties, prices, availability, applications, leases, payments.
- When recommending properties, calculate the TOTAL cost (rent + fees) and compare to user's budget.
- Give specific property names, prices, and locations — NEVER say "I cannot access the data".
- ALWAYS include direct links when mentioning properties, pages, or actions the user can take.
- If the user asks about their applications/leases/payments, give exact details from the data.
- Always respond in the SAME LANGUAGE the user writes in (French or English).
- Be concise, specific, and actionable.
- Use property names and details when recommending.
- If data is empty (no properties, no applications etc.), say so honestly and suggest next steps.

STRICT TOPIC RESTRICTION — MANDATORY:
You MUST ONLY discuss topics directly related to Prelease Canada: properties, rentals, applications, leases, payments, insurance, maintenance, support, platform features, and the rental process in Canada.
If the user asks about ANYTHING unrelated (weather, politics, recipes, sports, other websites, general knowledge, coding, etc.), respond ONLY with: "I'm Prelease AI and I can only assist you with questions about Prelease Canada. How can I help you find a property or manage your rental?"
Do NOT engage with off-topic subjects under any circumstances. Always bring the conversation back to Prelease Canada.

AGENTIC CAPABILITIES — You can TAKE REAL ACTIONS on behalf of the user using tools:
1. search_properties — Search and filter available properties by city, price, bedrooms, type
2. submit_application — Submit a rental application for a property
3. sign_lease — Sign a lease agreement (renter signature)
4. initiate_payment — Initiate payment for an active/signed lease

ACTION RULES (MANDATORY):
- When user asks to find a home/apartment/property → call search_properties immediately
- ALWAYS present search results to the user and ask "Do you want me to apply for [property name]?" before calling submit_application
- For submit_application, collect: monthly income, employment status, current address, move-in date, lease duration (3 or 6 months). Ask the user if any is missing.
- After submitting an application, inform the user and tell them to wait for landlord approval
- For sign_lease: confirm with user first ("I will sign lease #X on your behalf, confirm?")
- For initiate_payment: ask which payment method (credit_card, debit_card, bank_transfer, e_transfer) then proceed
- NEVER call submit_application, sign_lease or initiate_payment without explicit user confirmation
- After each action, tell the user exactly what was done and what the next step is
PROMPT;

        $baseUrl = $context['base_url'] ?? '';

        // ══════════ PROPERTIES ══════════
        if (!empty($context['all_properties'])) {
            $basePrompt .= "\n\n═══ ALL AVAILABLE PROPERTIES ON THE PLATFORM ═══\n";
            foreach ($context['all_properties'] as $p) {
                $amenities = !empty($p['amenities']) ? implode(', ', array_column($p['amenities'], 'name')) : 'N/A';
                $rating = $p['avg_rating'] ? number_format($p['avg_rating'], 1) . '/5' : 'No reviews';
                $slug = $p['slug'] ?? 'property';
                $url = "{$baseUrl}/property-detail/{$slug}/{$p['id']}";
                $basePrompt .= "• \"{$p['title']}\" — {$p['city']}, {$p['state']} | \${$p['set_your_price']}/month | {$p['how_many_bedrooms']} bed, {$p['how_many_bathroom']} bath | Type: {$p['describe_your_place']} | Guests: {$p['how_many_guests']} | Rating: {$rating} | Amenities: {$amenities} | Link: {$url}\n";
            }
        }

        // ══════════ USER'S PROPERTIES (landlord) ══════════
        if (!empty($context['my_properties'])) {
            $basePrompt .= "\n\n═══ YOUR PROPERTIES (as landlord) ═══\n";
            foreach ($context['my_properties'] as $p) {
                $slug = $p['slug'] ?? 'property';
                $url = "{$baseUrl}/property-detail/{$slug}/{$p['id']}";
                $basePrompt .= "• \"{$p['title']}\" — {$p['city']}, {$p['state']} | \${$p['set_your_price']}/month | {$p['how_many_bedrooms']} bed, {$p['how_many_bathroom']} bath | Link: {$url}\n";
            }
        }

        // ══════════ USER PREFERENCES ══════════
        if (!empty($context['preferences'])) {
            $pref = $context['preferences'];
            $basePrompt .= "\n\n═══ YOUR SEARCH PREFERENCES ═══\n";
            $basePrompt .= "City: " . ($pref['preferred_city'] ?? 'Any') . " | Budget: \${$pref['budget_min']} - \${$pref['budget_max']}/month | ";
            $basePrompt .= "Type: " . ($pref['property_type'] ?? 'Any') . " | Min bedrooms: " . ($pref['min_bedrooms'] ?? 'Any') . " | ";
            $basePrompt .= "Lease: " . ($pref['lease_duration'] ?? 'Any') . " | Pets: " . ($pref['pets_allowed'] ? 'Yes' : 'No') . "\n";
        }

        // ══════════ APPLICATIONS ══════════
        if (!empty($context['applications'])) {
            $basePrompt .= "\n\n═══ YOUR APPLICATIONS ═══\n";
            foreach ($context['applications'] as $app) {
                $propTitle = $app['property']['title'] ?? 'Property #' . $app['property_id'];
                $basePrompt .= "• Application #{$app['id']} for \"{$propTitle}\" | Status: {$app['status']} | Income: \${$app['monthly_income']}/month | Move-in: {$app['desired_move_in']} | Duration: {$app['desired_lease_duration']}\n";
            }
        }

        // ══════════ LEASES ══════════
        if (!empty($context['leases'])) {
            $basePrompt .= "\n\n═══ YOUR LEASES ═══\n";
            foreach ($context['leases'] as $l) {
                $propTitle = $l['property']['title'] ?? 'Property #' . $l['property_id'];
                $basePrompt .= "• Lease #{$l['id']} for \"{$propTitle}\" | Status: {$l['status']} | \${$l['monthly_rent']}/month | Total: \${$l['total_payable']} | {$l['start_date']} → {$l['end_date']} | Type: {$l['lease_type']}\n";
            }
        }

        // ══════════ PAYMENTS ══════════
        if (!empty($context['payments'])) {
            $basePrompt .= "\n\n═══ YOUR PAYMENTS ═══\n";
            foreach ($context['payments'] as $pay) {
                $basePrompt .= "• Payment {$pay['payment_reference']} | Status: {$pay['status']} | Total: \${$pay['total_amount']} | Method: {$pay['payment_method']} | Paid: " . ($pay['paid_at'] ?? 'Not yet') . "\n";
            }
        }

        // ══════════ WISHLIST ══════════
        if (!empty($context['wishlist'])) {
            $basePrompt .= "\n\n═══ YOUR WISHLIST ═══\n";
            foreach ($context['wishlist'] as $w) {
                $propTitle = $w['property']['title'] ?? 'Property #' . $w['property_id'];
                $basePrompt .= "• \"{$propTitle}\"\n";
            }
        }

        // ══════════ MAINTENANCE REQUESTS ══════════
        if (!empty($context['maintenance'])) {
            $basePrompt .= "\n\n═══ YOUR MAINTENANCE REQUESTS ═══\n";
            foreach ($context['maintenance'] as $m) {
                $basePrompt .= "• #{$m['id']} | {$m['title']} | Status: {$m['status']} | Priority: {$m['priority']}\n";
            }
        }

        // ══════════ INSURANCE ══════════
        if (!empty($context['insurance'])) {
            $basePrompt .= "\n\n═══ YOUR RENTAL INSURANCE ═══\n";
            foreach ($context['insurance'] as $ins) {
                $propTitle = $ins['lease_agreement']['property']['title'] ?? 'Lease #' . ($ins['lease_agreement_id'] ?? '?');
                $basePrompt .= "• Policy #{$ins['id']} ({$ins['policy_number']}) for \"{$propTitle}\" | Status: {$ins['status']} | Premium: \${$ins['premium_amount']}/month | Coverage: {$ins['coverage_start']} → {$ins['coverage_end']}\n";
            }
        }

        // ══════════ SUPPORT TICKETS ══════════
        if (!empty($context['support_tickets'])) {
            $basePrompt .= "\n\n═══ YOUR SUPPORT TICKETS ═══\n";
            foreach ($context['support_tickets'] as $t) {
                $basePrompt .= "• Ticket #{$t['id']} | Subject: {$t['subject']} | Status: {$t['status']} | Priority: {$t['priority']} | Created: {$t['created_at']}\n";
            }
        }

        // ══════════ PLATFORM STATS ══════════
        if (!empty($context['stats'])) {
            $basePrompt .= "\n\n═══ PLATFORM STATS ═══\n";
            $basePrompt .= "Total properties: {$context['stats']['total_properties']} | Total active leases: {$context['stats']['active_leases']} | Average rent: \${$context['stats']['avg_rent']}/month\n";
        }

        return $basePrompt;
    }

    private function getUserContext($user)
    {
        $context = [];

        try {
            $isRenter = in_array(strtolower($user->role), ['renter', 'tenant', null, '']);

            // ── Frontend base URL ──
            $context['base_url'] = config('services.frontend.url', 'https://www.preleasecanada.ca');

            // ── All available properties (for everyone) ──
            $context['all_properties'] = Property::with(['amenities:id,name', 'reviews'])
                ->select('id', 'title', 'slug', 'city', 'state', 'set_your_price', 'how_many_bedrooms', 'how_many_bathroom', 'describe_your_place', 'how_many_guests', 'user_id')
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get()
                ->map(function ($p) {
                    $p->avg_rating = $p->reviews->avg('rating');
                    unset($p->reviews);
                    return $p;
                })
                ->toArray();

            // ── Platform stats ──
            $context['stats'] = [
                'total_properties' => Property::count(),
                'active_leases' => LeaseAgreement::where('status', 'active')->count(),
                'avg_rent' => number_format(Property::avg('set_your_price') ?? 0, 2),
            ];

            if ($isRenter) {
                // ── Renter preferences ──
                $pref = RenterPreference::where('user_id', $user->id)->first();
                if ($pref) {
                    $context['preferences'] = $pref->toArray();
                }

                // ── Renter applications ──
                $context['applications'] = RentalApplication::with('property:id,title,city,state,set_your_price')
                    ->where('renter_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(20)
                    ->get()
                    ->toArray();

                // ── Renter leases ──
                $context['leases'] = LeaseAgreement::with('property:id,title,city,state')
                    ->where('renter_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->toArray();

                // ── Renter payments ──
                $context['payments'] = Payment::where('renter_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->toArray();

                // ── Wishlist ──
                $context['wishlist'] = WishList::with('property:id,title,city,state,set_your_price')
                    ->where('user_id', $user->id)
                    ->get()
                    ->toArray();

                // ── Maintenance ──
                $context['maintenance'] = MaintenanceRequest::where('tenant_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->toArray();

                // ── Insurance ──
                $context['insurance'] = RentalInsurance::with(['leaseAgreement.property:id,title,city,state'])
                    ->where('renter_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->toArray();

                // ── Support tickets ──
                $context['support_tickets'] = SupportTicket::where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->toArray();

            } else {
                // ── Landlord's own properties ──
                $context['my_properties'] = Property::select('id', 'title', 'slug', 'city', 'state', 'set_your_price', 'how_many_bedrooms', 'how_many_bathroom')
                    ->where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->toArray();

                // ── Applications received ──
                $context['applications'] = RentalApplication::with(['property:id,title', 'renter:id,first_name,last_name,email'])
                    ->where('landlord_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(20)
                    ->get()
                    ->toArray();

                // ── Landlord leases ──
                $context['leases'] = LeaseAgreement::with('property:id,title,city,state')
                    ->where('landlord_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->toArray();

                // ── Landlord payments received ──
                $context['payments'] = Payment::where('landlord_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->toArray();

                // ── Maintenance for landlord ──
                $context['maintenance'] = MaintenanceRequest::where('landlord_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->toArray();

                // ── Support tickets ──
                $context['support_tickets'] = SupportTicket::where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->toArray();
            }
        } catch (\Throwable $e) {
            Log::warning('AI context fetch error: ' . $e->getMessage());
        }

        return $context;
    }

    // ══════════════════════════════════════════════════════
    // TOOL DEFINITIONS
    // ══════════════════════════════════════════════════════

    private function getTools(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_properties',
                    'description' => 'Search available properties on the platform. Call this whenever the user wants to find a property.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'city'          => ['type' => 'string',  'description' => 'City name e.g. Montreal, Toronto, Vancouver, Edmonton, Ottawa'],
                            'max_price'     => ['type' => 'number',  'description' => 'Maximum monthly price in CAD'],
                            'min_price'     => ['type' => 'number',  'description' => 'Minimum monthly price in CAD'],
                            'min_bedrooms'  => ['type' => 'integer', 'description' => 'Minimum bedrooms'],
                            'property_type' => ['type' => 'string',  'description' => 'Apartment, House, Condo, Townhouse, Studio, Loft, Duplex, Room'],
                            'max_results'   => ['type' => 'integer', 'description' => 'Max results (default 5)'],
                        ],
                        'required' => [],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'submit_application',
                    'description' => 'Submit a rental application for a property on behalf of the user. Only call after user explicitly confirms.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'property_id'            => ['type' => 'integer', 'description' => 'Property ID'],
                            'cover_letter'           => ['type' => 'string',  'description' => 'Professional cover letter'],
                            'employment_status'      => ['type' => 'string',  'description' => 'employed, self_employed, student, unemployed'],
                            'monthly_income'         => ['type' => 'number',  'description' => 'Monthly income in CAD'],
                            'current_address'        => ['type' => 'string',  'description' => 'Current address'],
                            'reason_for_moving'      => ['type' => 'string',  'description' => 'Reason for moving'],
                            'number_of_occupants'    => ['type' => 'integer', 'description' => 'Number of occupants (default 1)'],
                            'has_pets'               => ['type' => 'boolean', 'description' => 'Has pets?'],
                            'desired_move_in'        => ['type' => 'string',  'description' => 'Move-in date YYYY-MM-DD'],
                            'desired_lease_duration' => ['type' => 'string',  'description' => '3_month or 6_month'],
                        ],
                        'required' => ['property_id', 'employment_status', 'monthly_income', 'current_address', 'desired_move_in', 'desired_lease_duration'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'sign_lease',
                    'description' => 'Sign a lease agreement on behalf of the renter. Only call after explicit user confirmation.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'lease_id' => ['type' => 'integer', 'description' => 'Lease agreement ID'],
                        ],
                        'required' => ['lease_id'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'initiate_payment',
                    'description' => 'Initiate payment for a lease. Only call after user confirms and provides payment method.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'lease_agreement_id' => ['type' => 'integer', 'description' => 'Lease ID'],
                            'payment_method'     => ['type' => 'string',  'description' => 'credit_card, debit_card, bank_transfer, e_transfer'],
                        ],
                        'required' => ['lease_agreement_id', 'payment_method'],
                    ],
                ],
            ],
        ];
    }

    // ══════════════════════════════════════════════════════
    // TOOL EXECUTOR
    // ══════════════════════════════════════════════════════

    private function executeTool(string $name, array $args, $user): string
    {
        try {
            switch ($name) {
                case 'search_properties':   return $this->toolSearchProperties($args);
                case 'submit_application':  return $this->toolSubmitApplication($args, $user);
                case 'sign_lease':          return $this->toolSignLease($args, $user);
                case 'initiate_payment':    return $this->toolInitiatePayment($args, $user);
                default:                    return json_encode(['error' => 'Unknown tool: ' . $name]);
            }
        } catch (\Throwable $e) {
            Log::error("Tool [{$name}] error: " . $e->getMessage());
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    private function toolSearchProperties(array $args): string
    {
        $query = Property::with(['amenities:id,name'])
            ->select('id', 'title', 'slug', 'city', 'state', 'set_your_price', 'how_many_bedrooms', 'how_many_bathroom', 'describe_your_place', 'how_many_guests');

        if (!empty($args['city']))
            $query->where('city', 'LIKE', '%' . $args['city'] . '%');
        if (!empty($args['max_price']))
            $query->whereRaw('CAST(set_your_price AS DECIMAL(10,2)) <= ?', [$args['max_price']]);
        if (!empty($args['min_price']))
            $query->whereRaw('CAST(set_your_price AS DECIMAL(10,2)) >= ?', [$args['min_price']]);
        if (!empty($args['min_bedrooms']))
            $query->where('how_many_bedrooms', '>=', $args['min_bedrooms']);
        if (!empty($args['property_type']))
            $query->where('describe_your_place', $args['property_type']);

        $limit = min((int)($args['max_results'] ?? 5), 10);
        $properties = $query->orderByRaw('CAST(set_your_price AS DECIMAL(10,2)) ASC')->limit($limit)->get();

        if ($properties->isEmpty()) {
            return json_encode(['found' => 0, 'message' => 'No properties found matching the criteria.']);
        }

        $results = $properties->map(fn($p) => [
            'id'           => $p->id,
            'title'        => $p->title,
            'city'         => $p->city,
            'state'        => $p->state,
            'price'        => $p->set_your_price,
            'bedrooms'     => $p->how_many_bedrooms,
            'bathrooms'    => $p->how_many_bathroom,
            'type'         => $p->describe_your_place,
            'max_guests'   => $p->how_many_guests,
            'amenities'    => $p->amenities->pluck('name')->implode(', '),
        ])->toArray();

        return json_encode(['found' => count($results), 'properties' => $results]);
    }

    private function toolSubmitApplication(array $args, $user): string
    {
        $property = Property::find($args['property_id']);
        if (!$property) return json_encode(['error' => 'Property not found.']);

        $existing = RentalApplication::where('property_id', $property->id)
            ->where('renter_id', $user->id)
            ->whereIn('status', ['draft', 'submitted', 'under_review'])
            ->first();
        if ($existing)
            return json_encode(['error' => 'You already have an active application for this property (Application #' . $existing->id . ').']);

        $coverLetter = $args['cover_letter'] ?? 'I am very interested in renting this property and would be a reliable tenant.';

        $application = RentalApplication::create([
            'property_id'            => $property->id,
            'renter_id'              => $user->id,
            'landlord_id'            => $property->user_id,
            'cover_letter'           => $coverLetter,
            'employment_status'      => $args['employment_status'],
            'monthly_income'         => $args['monthly_income'],
            'current_address'        => $args['current_address'],
            'reason_for_moving'      => $args['reason_for_moving'] ?? 'Looking for a new home.',
            'number_of_occupants'    => $args['number_of_occupants'] ?? 1,
            'has_pets'               => $args['has_pets'] ?? false,
            'desired_move_in'        => $args['desired_move_in'],
            'desired_lease_duration' => $args['desired_lease_duration'],
            'status'                 => 'submitted',
            'submitted_at'           => now(),
        ]);

        try {
            $landlord = User::find($property->user_id);
            if ($landlord) $landlord->notify(new ApplicationStatusNotification($application, 'landlord'));
        } catch (\Throwable $e) {
            Log::warning('AI: application notification failed: ' . $e->getMessage());
        }

        return json_encode([
            'success'        => true,
            'application_id' => $application->id,
            'property'       => $property->title,
            'status'         => 'submitted',
            'message'        => 'Application #' . $application->id . ' submitted for "' . $property->title . '". The landlord will review it and respond.',
        ]);
    }

    private function toolSignLease(array $args, $user): string
    {
        $lease = LeaseAgreement::where('renter_id', $user->id)
            ->where('status', 'pending_renter_signature')
            ->find($args['lease_id']);

        if (!$lease)
            return json_encode(['error' => 'Lease #' . $args['lease_id'] . ' not found or not awaiting your signature.']);

        $lease->renter_signed_at = now();
        $lease->status = 'pending_landlord_signature';
        $lease->save();

        try {
            $landlord = User::find($lease->landlord_id);
            if ($landlord) $landlord->notify(new LeaseReminderNotification($lease, 'landlord', 'signing'));
        } catch (\Throwable $e) {
            Log::warning('AI: lease sign notification failed: ' . $e->getMessage());
        }

        return json_encode([
            'success'  => true,
            'lease_id' => $lease->id,
            'status'   => 'pending_landlord_signature',
            'message'  => 'Lease #' . $lease->id . ' signed. Now waiting for landlord signature before it becomes active.',
        ]);
    }

    private function toolInitiatePayment(array $args, $user): string
    {
        $lease = LeaseAgreement::where('renter_id', $user->id)
            ->whereIn('status', ['active', 'pending_landlord_signature'])
            ->find($args['lease_agreement_id']);

        if (!$lease)
            return json_encode(['error' => 'Lease #' . $args['lease_agreement_id'] . ' not found or not ready for payment.']);

        $existing = Payment::where('lease_agreement_id', $lease->id)
            ->whereIn('status', ['completed', 'processing', 'pending'])
            ->first();
        if ($existing)
            return json_encode(['error' => 'A payment already exists for this lease (Payment #' . $existing->id . ', status: ' . $existing->status . ').']);

        $months = $lease->lease_type === '3_month' ? 3 : 6;
        $rentAmount    = (float)$lease->total_rent;
        $supportFee    = 100.00 * $months;
        $commissionFee = $rentAmount * 0.05;
        $insuranceFee  = (float)($lease->insurance_fee ?? 0);
        $totalAmount   = $rentAmount + $supportFee + $commissionFee + $insuranceFee;

        $payment = Payment::create([
            'payment_reference'       => 'PRL-' . strtoupper(Str::random(10)),
            'lease_agreement_id'      => $lease->id,
            'renter_id'               => $user->id,
            'landlord_id'             => $lease->landlord_id,
            'property_id'             => $lease->property_id,
            'rent_amount'             => $rentAmount,
            'support_fee'             => $supportFee,
            'commission_fee'          => $commissionFee,
            'insurance_fee'           => $insuranceFee,
            'total_amount'            => $totalAmount,
            'payment_type'            => 'full_upfront',
            'payment_method'          => $args['payment_method'],
            'status'                  => 'pending',
            'installment_number'      => 1,
            'total_installments'      => 1,
            'due_date'                => now()->toDateString(),
            'installment_group'       => 'PRL-GRP-' . strtoupper(Str::random(8)),
            'landlord_payout_status'  => 'pending',
            'landlord_payout_amount'  => $rentAmount,
            'insurance_payout_status' => $insuranceFee > 0 ? 'pending' : 'not_applicable',
            'insurance_payout_amount' => $insuranceFee > 0 ? $insuranceFee : null,
        ]);

        return json_encode([
            'success'    => true,
            'payment_id' => $payment->id,
            'reference'  => $payment->payment_reference,
            'total'      => '$' . number_format($totalAmount, 2),
            'method'     => $args['payment_method'],
            'status'     => 'pending',
            'message'    => 'Payment ' . $payment->payment_reference . ' initiated for $' . number_format($totalAmount, 2) . ' via ' . $args['payment_method'] . '. Status: pending.',
        ]);
    }

    // ══════════════════════════════════════════════════════
    // MAIN CHAT ENDPOINT (agentic loop + persistent history)
    // ══════════════════════════════════════════════════════

    public function chat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:2000',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = Auth::guard('api')->user();
            if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

            $apiKey = config('services.groq.api_key');
            if (!$apiKey) return response()->json(['error' => 'AI service not configured'], 503);

            $context      = $this->getUserContext($user);
            $systemPrompt = $this->getSystemPrompt($user, $context);
            $model        = config('services.groq.model', 'llama-3.3-70b-versatile');
            $tools        = $this->getTools();

            // ── Load persistent history from DB (last 60 rows = 30 exchanges) ──
            $dbHistory = AiChatHistory::where('user_id', $user->id)
                ->orderBy('created_at', 'asc')
                ->limit(60)
                ->get();

            $messages = [['role' => 'system', 'content' => $systemPrompt]];
            foreach ($dbHistory as $h) {
                $messages[] = ['role' => $h->role, 'content' => $h->content];
            }
            $messages[] = ['role' => 'user', 'content' => $request->message];

            // ── Persist user message ──
            AiChatHistory::create([
                'user_id' => $user->id,
                'role'    => 'user',
                'content' => $request->message,
            ]);

            // ── Agentic loop (max 4 iterations) ──
            $reply = null;
            for ($i = 0; $i < 4; $i++) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                ])->timeout(90)->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model'       => $model,
                    'messages'    => $messages,
                    'tools'       => $tools,
                    'tool_choice' => 'auto',
                    'max_tokens'  => 1500,
                    'temperature' => 0.7,
                ]);

                if ($response->failed()) {
                    Log::error('Groq API error', ['status' => $response->status(), 'body' => $response->body()]);
                    return response()->json(['error' => 'AI service temporarily unavailable: ' . ($response->json()['error']['message'] ?? 'Unknown error')], 503);
                }

                $data    = $response->json();
                $choice  = $data['choices'][0] ?? null;
                $aiMsg   = $choice['message'] ?? null;

                if (empty($aiMsg['tool_calls'])) {
                    $reply = $aiMsg['content'] ?? 'Sorry, I could not generate a response.';
                    break;
                }

                $messages[] = $aiMsg;

                foreach ($aiMsg['tool_calls'] as $toolCall) {
                    $toolName   = $toolCall['function']['name'] ?? '';
                    $toolArgs   = json_decode($toolCall['function']['arguments'] ?? '{}', true) ?? [];
                    $toolResult = $this->executeTool($toolName, $toolArgs, $user);
                    Log::info("AI tool: [{$toolName}]", ['args' => $toolArgs, 'result' => substr($toolResult, 0, 200)]);
                    $messages[] = [
                        'role'         => 'tool',
                        'tool_call_id' => $toolCall['id'],
                        'content'      => $toolResult,
                    ];
                }
            }

            if (!$reply) {
                $reply = 'Actions completed. Please check your applications/leases/payments for updates.';
            }

            // ── Persist assistant reply ──
            AiChatHistory::create([
                'user_id' => $user->id,
                'role'    => 'assistant',
                'content' => $reply,
            ]);

            return response()->json(['status' => 200, 'reply' => $reply]);

        } catch (\Throwable $th) {
            Log::error('AI Assistant error: ' . $th->getMessage());
            return response()->json(['error' => 'An error occurred. Please try again.'], 500);
        }
    }

    public function history(Request $request)
    {
        try {
            $user = Auth::guard('api')->user();
            if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

            $messages = AiChatHistory::where('user_id', $user->id)
                ->orderBy('created_at', 'asc')
                ->limit(100)
                ->get(['id', 'role', 'content', 'created_at']);

            return response()->json(['status' => 200, 'data' => $messages]);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function clearHistory(Request $request)
    {
        try {
            $user = Auth::guard('api')->user();
            if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

            AiChatHistory::where('user_id', $user->id)->delete();

            return response()->json(['status' => 200, 'message' => 'Chat history cleared.']);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function suggestions(Request $request)
    {
        try {
            $user = Auth::guard('api')->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $role = $user->role;
            $isRenter = in_array($role, ['renter', 'Tenant', null, '']);

            if ($isRenter) {
                $appCount = RentalApplication::where('renter_id', $user->id)->count();
                $leaseCount = LeaseAgreement::where('renter_id', $user->id)->count();
                $propCount = Property::count();

                $suggestions = [];
                if ($propCount > 0) {
                    $suggestions[] = "Show me all available properties";
                    $suggestions[] = "Find me something under \$1500/month";
                }
                $suggestions[] = "How does the rental process work?";
                if ($appCount > 0) {
                    $suggestions[] = "What's the status of my applications?";
                }
                if ($leaseCount > 0) {
                    $suggestions[] = "Show me my lease details";
                }
                $suggestions[] = "Calculate total cost for a 3-month lease";
            } else {
                $appCount = RentalApplication::where('landlord_id', $user->id)->whereIn('status', ['submitted', 'under_review'])->count();
                $propCount = Property::where('user_id', $user->id)->count();

                $suggestions = [];
                if ($propCount > 0) {
                    $suggestions[] = "Show me my properties";
                }
                if ($appCount > 0) {
                    $suggestions[] = "I have {$appCount} pending application(s)";
                }
                $suggestions[] = "How do lease agreements work?";
                $suggestions[] = "Explain the commission structure";
                $suggestions[] = "How do I list a new property?";
            }

            return response()->json(['status' => 200, 'suggestions' => array_slice($suggestions, 0, 5)]);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
}
