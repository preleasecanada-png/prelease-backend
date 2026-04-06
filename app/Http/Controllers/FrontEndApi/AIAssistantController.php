<?php

namespace App\Http\Controllers\FrontEndApi;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\LeaseAgreement;
use App\Models\RentalApplication;
use App\Models\Payment;
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

        $basePrompt = <<<PROMPT
You are Prelease AI, a friendly and knowledgeable assistant for Prelease Canada — a rental platform connecting landlords and tenants in Canada.

Current user: {$name} (role: {$role})

YOUR ROLE:
- Help users find properties that match their budget and needs
- Explain the rental process step by step
- Advise on lease types (3-month or 6-month)
- Explain fees: support fee (\$100/month), commission (5% of total rent), insurance
- Help landlords manage their properties and applications
- Answer questions about the platform features
- Always respond in the same language the user writes in (French or English)

RENTAL PROCESS:
1. Tenant browses properties and submits an application
2. Landlord reviews and approves/rejects the application
3. If approved, landlord creates a lease agreement
4. Both parties sign the lease (tenant first, then landlord)
5. Tenant makes full upfront payment
6. Lease becomes active, rental insurance is created automatically

FEE STRUCTURE (for a lease):
- Monthly rent × number of months = Total rent
- Support fee: \$100/month × number of months
- Commission: 5% of total rent
- Insurance: determined later
- Total = Rent + Support + Commission + Insurance

Example: 3-month lease at \$1,500/month
- Rent: \$4,500
- Support: \$300
- Commission: \$225
- Total: \$5,025

GUIDELINES:
- Be concise but helpful
- If the user asks about a specific property, use the context provided
- Recommend properties based on budget: suggest properties where total cost fits their means
- If you don't know something specific, say so honestly
- Never make up property listings or prices
- Always be encouraging and supportive
PROMPT;

        if (!empty($context['properties'])) {
            $propList = collect($context['properties'])->map(function ($p) {
                return "- {$p['title']} in {$p['city']}, {$p['state']}: \${$p['price']}/month, {$p['bedrooms']} bed, {$p['bathrooms']} bath";
            })->join("\n");
            $basePrompt .= "\n\nAVAILABLE PROPERTIES THE USER MIGHT BE INTERESTED IN:\n{$propList}";
        }

        if (!empty($context['applications_count'])) {
            $basePrompt .= "\n\nUser has {$context['applications_count']} active application(s).";
        }

        if (!empty($context['leases_count'])) {
            $basePrompt .= "\n\nUser has {$context['leases_count']} active lease(s).";
        }

        return $basePrompt;
    }

    private function getUserContext($user)
    {
        $context = [];

        try {
            if (in_array($user->role, ['renter', 'Tenant', null])) {
                $context['properties'] = Property::select('id', 'title', 'city', 'state', 'price', 'bedrooms', 'bathrooms', 'property_type')
                    ->where('status', 'active')
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->toArray();

                $context['applications_count'] = RentalApplication::where('renter_id', $user->id)
                    ->whereIn('status', ['submitted', 'under_review', 'approved'])
                    ->count();

                $context['leases_count'] = LeaseAgreement::where('renter_id', $user->id)
                    ->where('status', 'active')
                    ->count();
            } else {
                $context['properties'] = Property::select('id', 'title', 'city', 'state', 'price', 'bedrooms', 'bathrooms', 'property_type')
                    ->where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->toArray();

                $pendingApps = RentalApplication::where('landlord_id', $user->id)
                    ->whereIn('status', ['submitted', 'under_review'])
                    ->count();
                $context['applications_count'] = $pendingApps;

                $context['leases_count'] = LeaseAgreement::where('landlord_id', $user->id)
                    ->where('status', 'active')
                    ->count();
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

            $apiKey = env('GEMINI_API_KEY');
            if (!$apiKey) {
                return response()->json(['error' => 'AI service not configured'], 503);
            }

            $context = $this->getUserContext($user);
            $systemPrompt = $this->getSystemPrompt($user, $context);

            // Build Gemini contents array
            $contents = [];

            // Add conversation history (last 10 exchanges max)
            $history = $request->conversation ?? [];
            $history = array_slice($history, -20);
            foreach ($history as $msg) {
                if (isset($msg['role']) && isset($msg['content'])) {
                    $contents[] = [
                        'role' => $msg['role'] === 'assistant' ? 'model' : 'user',
                        'parts' => [['text' => $msg['content']]],
                    ];
                }
            }

            // Add current message
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $request->message]],
            ];

            $model = env('GEMINI_MODEL', 'gemini-2.5-flash');
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->timeout(30)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                [
                    'system_instruction' => [
                        'parts' => [['text' => $systemPrompt]],
                    ],
                    'contents' => $contents,
                    'generationConfig' => [
                        'maxOutputTokens' => 800,
                        'temperature' => 0.7,
                    ],
                ]
            );

            if ($response->failed()) {
                Log::error('Gemini API error', ['status' => $response->status(), 'body' => $response->body()]);
                return response()->json(['error' => 'AI service temporarily unavailable: ' . ($response->json()['error']['message'] ?? 'Unknown error')], 503);
            }

            $data = $response->json();
            $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Sorry, I could not generate a response.';

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
            $suggestions = [];

            if (in_array($role, ['renter', 'Tenant', null])) {
                $suggestions = [
                    'What properties are available within my budget?',
                    'How does the rental process work?',
                    'Explain the fees and costs',
                    'Help me find a 2-bedroom apartment',
                    'What documents do I need to apply?',
                ];
            } else {
                $suggestions = [
                    'How do I list a new property?',
                    'I have pending applications, what should I do?',
                    'How do lease agreements work?',
                    'Explain the commission structure',
                    'How do I manage my properties?',
                ];
            }

            return response()->json(['status' => 200, 'suggestions' => $suggestions]);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
}
