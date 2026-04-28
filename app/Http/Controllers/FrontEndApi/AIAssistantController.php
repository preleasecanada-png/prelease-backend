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
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AIAssistantController extends Controller
{
    private function getSystemPrompt($user, $context)
    {
        $role = $user->role ?? 'user';
        $name = $user->first_name ?? 'User';
        $email = $user->email ?? '';

        $basePrompt = <<<PROMPT
You are Prelease AI, the intelligent assistant for the Prelease Canada rental platform. You have FULL ACCESS to real-time platform data. You can see all properties, applications, leases, payments, and user data. USE THIS DATA to give specific, actionable answers.

CURRENT USER: {$name} ({$email}), Role: {$role}

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
            }
        } catch (\Throwable $e) {
            Log::warning('AI context fetch error: ' . $e->getMessage());
        }

        return $context;
    }

    public function chat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:2000',
            'conversation' => 'nullable|array',
            'conversation.*.role' => 'in:user,assistant',
            'conversation.*.content' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = Auth::guard('api')->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $apiKey = config('services.groq.api_key');
            if (!$apiKey) {
                return response()->json(['error' => 'AI service not configured'], 503);
            }

            $context = $this->getUserContext($user);
            $systemPrompt = $this->getSystemPrompt($user, $context);

            // Build Groq/OpenAI compatible contents array
            $messages = [];
            
            $messages[] = [
                'role' => 'system',
                'content' => $systemPrompt
            ];

            // Add conversation history (last 20 messages max)
            $history = $request->conversation ?? [];
            $history = array_slice($history, -20);
            foreach ($history as $msg) {
                if (isset($msg['role']) && isset($msg['content'])) {
                    $messages[] = [
                        'role' => $msg['role'] === 'assistant' ? 'assistant' : 'user',
                        'content' => $msg['content']
                    ];
                }
            }

            // Add current message
            $messages[] = [
                'role' => 'user',
                'content' => $request->message
            ];

            $model = config('services.groq.model', 'llama-3.1-8b-instant');

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post(
                "https://api.groq.com/openai/v1/chat/completions",
                [
                    'model' => $model,
                    'messages' => $messages,
                    'max_tokens' => 1200,
                    'temperature' => 0.7,
                ]
            );

            if ($response->failed()) {
                Log::error('Groq API error', ['status' => $response->status(), 'body' => $response->body()]);
                return response()->json(['error' => 'AI service temporarily unavailable: ' . ($response->json()['error']['message'] ?? 'Unknown error')], 503);
            }

            $data = $response->json();
            $reply = $data['choices'][0]['message']['content'] ?? 'Sorry, I could not generate a response.';

            return response()->json([
                'status' => 200,
                'reply' => $reply,
            ]);
        } catch (\Throwable $th) {
            Log::error('AI Assistant error: ' . $th->getMessage());
            return response()->json(['error' => 'An error occurred. Please try again.'], 500);
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
