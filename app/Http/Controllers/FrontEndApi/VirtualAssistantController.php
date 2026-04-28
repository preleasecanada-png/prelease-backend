<?php

namespace App\Http\Controllers\FrontEndApi;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VirtualAssistantConversation;
use App\Models\VirtualAssistantMessage;
use App\Models\VirtualAssistantSetting;
use App\Services\AIAssistantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VirtualAssistantController extends Controller
{
    private AIAssistantService $aiService;

    public function __construct(AIAssistantService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function startConversation(Request $request)
    {
        $request->validate([
            'channel' => 'in:chat,sms,phone',
            'phone_number' => 'nullable|string',
            'subject' => 'nullable|string',
        ]);

        $user = Auth::user();
        
        // Check if user already has an active conversation
        $existingConversation = VirtualAssistantConversation::where('user_id', $user->id)
            ->where('channel', $request->channel)
            ->where('status', 'active')
            ->first();

        if ($existingConversation) {
            return response()->json([
                'success' => true,
                'conversation_id' => $existingConversation->id,
                'message' => 'Existing conversation retrieved',
                'conversation' => $existingConversation->load('messages'),
            ]);
        }

        $conversation = $this->aiService->createConversation(
            $user->id,
            $request->channel ?? 'chat',
            $request->phone_number
        );

        if ($request->subject) {
            $conversation->subject = $request->subject;
            $conversation->save();
        }

        // Send welcome message
        $welcomeMessage = $this->getWelcomeMessage();
        $this->aiService->saveMessage(
            $conversation->id,
            'assistant',
            $welcomeMessage,
            true,
            null,
            null
        );

        return response()->json([
            'success' => true,
            'conversation_id' => $conversation->id,
            'message' => 'Conversation started',
            'conversation' => $conversation->load('messages'),
        ]);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:virtual_assistant_conversations,id',
            'message' => 'required|string',
        ]);

        $user = Auth::user();
        $conversation = VirtualAssistantConversation::findOrFail($request->conversation_id);

        // Verify user owns this conversation
        if ($conversation->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Save user message
        $this->aiService->saveMessage(
            $conversation->id,
            'user',
            $request->message,
            false,
            null,
            null
        );

        // Check business hours for phone/SMS channels
        if (!$this->aiService->isWithinBusinessHours() && in_array($conversation->channel, ['phone', 'sms'])) {
            $businessHoursMessage = $this->aiService->getBusinessHoursMessage();
            
            $this->aiService->saveMessage(
                $conversation->id,
                'assistant',
                $businessHoursMessage,
                true,
                null,
                null
            );

            return response()->json([
                'success' => true,
                'message' => $businessHoursMessage,
                'is_delayed' => true,
            ]);
        }

        // Generate AI response
        $aiResponse = $this->aiService->generateResponse(
            $request->message,
            $conversation,
            $user
        );

        // Save AI response
        if ($aiResponse['success']) {
            $this->aiService->saveMessage(
                $conversation->id,
                'assistant',
                $aiResponse['message'],
                true,
                $aiResponse['model_used'],
                $aiResponse['tokens_used']
            );
        }

        return response()->json($aiResponse);
    }

    public function getConversation(Request $request, $conversationId)
    {
        $user = Auth::user();
        $conversation = VirtualAssistantConversation::with('messages')
            ->where('id', $conversationId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'conversation' => $conversation,
        ]);
    }

    public function getConversations(Request $request)
    {
        $user = Auth::user();
        
        $conversations = VirtualAssistantConversation::with(['messages' => function ($query) {
            $query->orderBy('created_at', 'desc')->limit(1);
        }])
        ->where('user_id', $user->id)
        ->orderBy('updated_at', 'desc')
        ->get();

        return response()->json([
            'success' => true,
            'conversations' => $conversations,
        ]);
    }

    public function closeConversation(Request $request, $conversationId)
    {
        $user = Auth::user();
        $conversation = VirtualAssistantConversation::where('id', $conversationId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $conversation->status = 'closed';
        $conversation->save();

        return response()->json([
            'success' => true,
            'message' => 'Conversation closed',
        ]);
    }

    public function getSettings()
    {
        $settings = VirtualAssistantSetting::all();
        
        $formattedSettings = [];
        foreach ($settings as $setting) {
            $formattedSettings[$setting->key] = [
                'value' => $setting->value,
                'type' => $setting->type,
                'description' => $setting->description,
            ];
        }

        return response()->json([
            'success' => true,
            'settings' => $formattedSettings,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
        ]);

        foreach ($request->settings as $settingData) {
            VirtualAssistantSetting::set(
                $settingData['key'],
                $settingData['value'],
                $settingData['type'] ?? 'string',
                $settingData['description'] ?? null
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated',
        ]);
    }

    private function getWelcomeMessage(): string
    {
        return "Hello! I'm your Prelease Canada virtual assistant. I'm here to help you with questions about properties, rentals, leases, and account management. How can I assist you today?";
    }

    // Twilio Webhook endpoints (no authentication required for webhooks)
    public function handleIncomingSMS(Request $request)
    {
        $from = $request->input('From');
        $body = $request->input('Body');
        
        // Find or create conversation for this phone number
        $conversation = VirtualAssistantConversation::where('phone_number', $from)
            ->where('channel', 'sms')
            ->where('status', 'active')
            ->first();

        if (!$conversation) {
            // Try to find user by phone number
            $user = User::where('phone', $from)->first();
            
            if (!$user) {
                return response($this->aiService->generateTwiML(
                    "Désolé, je ne trouve pas votre compte. Veuillez vous connecter à votre compte Prelease Canada pour utiliser l'assistant virtuel."
                ), 200)->header('Content-Type', 'application/xml');
            }

            $conversation = $this->aiService->createConversation($user->id, 'sms', $from);
        }

        // Save user message
        $this->aiService->saveMessage($conversation->id, 'user', $body);

        // Check business hours
        if (!$this->aiService->isWithinBusinessHours()) {
            $businessHoursMessage = $this->aiService->getBusinessHoursMessage();
            $this->aiService->saveMessage($conversation->id, 'assistant', $businessHoursMessage, true);
            
            return response($this->aiService->generateTwiML($businessHoursMessage), 200)
                ->header('Content-Type', 'application/xml');
        }

        // Generate AI response
        $aiResponse = $this->aiService->generateResponse($body, $conversation, $conversation->user);

        if ($aiResponse['success']) {
            $this->aiService->saveMessage(
                $conversation->id,
                'assistant',
                $aiResponse['message'],
                true,
                $aiResponse['model_used'],
                $aiResponse['tokens_used']
            );

            // Send response via Twilio SMS
            $twilioService = new \App\Services\TwilioService();
            $twilioService->sendSMS($from, $aiResponse['message']);
        }

        return response('', 200);
    }

    public function handleIncomingCall(Request $request)
    {
        $from = $request->input('From');
        
        // Find or create conversation
        $conversation = VirtualAssistantConversation::where('phone_number', $from)
            ->where('channel', 'phone')
            ->where('status', 'active')
            ->first();

        if (!$conversation) {
            $user = User::where('phone', $from)->first();
            
            if (!$user) {
                return response($this->aiService->generateTwiML(
                    "Désolé, je ne trouve pas votre compte. Veuillez vous connecter à votre compte Prelease Canada pour utiliser l'assistant virtuel."
                ), 200)->header('Content-Type', 'application/xml');
            }

            $conversation = $this->aiService->createConversation($user->id, 'phone', $from);
        }

        // Check business hours
        if (!$this->aiService->isWithinBusinessHours()) {
            $businessHoursMessage = $this->aiService->getBusinessHoursMessage();
            return response($this->aiService->generateTwiML($businessHoursMessage), 200)
                ->header('Content-Type', 'application/xml');
        }

        $welcomeMessage = "Bonjour! Je suis votre assistant virtuel Prelease Canada. Comment puis-je vous aider aujourd'hui?";
        return response($this->aiService->generateTwiML($welcomeMessage, true), 200)
            ->header('Content-Type', 'application/xml');
    }

    public function handleVoiceGather(Request $request)
    {
        $speechResult = $request->input('SpeechResult');
        $from = $request->input('From');
        
        if (empty($speechResult)) {
            return response($this->aiService->generateTwiML("Je n'ai pas entendu votre réponse. Veuillez réessayer.", true), 200)
                ->header('Content-Type', 'application/xml');
        }

        $conversation = VirtualAssistantConversation::where('phone_number', $from)
            ->where('channel', 'phone')
            ->where('status', 'active')
            ->first();

        if (!$conversation) {
            return response($this->aiService->generateTwiML("Une erreur s'est produite. Veuillez rappeler."), 200)
                ->header('Content-Type', 'application/xml');
        }

        // Save user message
        $this->aiService->saveMessage($conversation->id, 'user', $speechResult);

        // Generate AI response
        $aiResponse = $this->aiService->generateResponse($speechResult, $conversation, $conversation->user);

        if ($aiResponse['success']) {
            $this->aiService->saveMessage(
                $conversation->id,
                'assistant',
                $aiResponse['message'],
                true,
                $aiResponse['model_used'],
                $aiResponse['tokens_used']
            );

            return response($this->aiService->generateTwiML($aiResponse['message'], true), 200)
                ->header('Content-Type', 'application/xml');
        }

        return response($this->aiService->generateTwiML("Désolé, une erreur s'est produite. Veuillez réessayer.", true), 200)
            ->header('Content-Type', 'application/xml');
    }
}
