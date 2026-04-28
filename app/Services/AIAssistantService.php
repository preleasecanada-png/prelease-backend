<?php

namespace App\Services;

use App\Models\VirtualAssistantConversation;
use App\Models\VirtualAssistantMessage;
use App\Models\VirtualAssistantSetting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIAssistantService
{
    private string $apiKey;
    private string $model;
    private string $systemPrompt;
    private int $maxTokens;
    private float $temperature;

    public function __construct()
    {
        $this->apiKey = env('OPENAI_API_KEY', '');
        $this->model = VirtualAssistantSetting::get('ai_model', 'gpt-4');
        $this->systemPrompt = VirtualAssistantSetting::get('system_prompt', 
            'You are a helpful assistant for Prelease Canada, a platform connecting renters and landlords. You help users with questions about properties, rentals, leases, and account management.'
        );
        $this->maxTokens = VirtualAssistantSetting::get('max_tokens', 1000);
        $this->temperature = VirtualAssistantSetting::get('temperature', 0.7);
    }

    public function generateResponse(string $userMessage, ?VirtualAssistantConversation $conversation = null, ?User $user = null): array
    {
        $messages = $this->buildConversationContext($userMessage, $conversation, $user);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => $messages,
                'max_tokens' => $this->maxTokens,
                'temperature' => $this->temperature,
            ]);

            if (!$response->successful()) {
                Log::error('OpenAI API Error: ' . $response->body());
                return [
                    'success' => false,
                    'message' => 'Sorry, I encountered an error. Please try again.',
                    'error' => $response->body(),
                ];
            }

            $data = $response->json();
            $aiMessage = $data['choices'][0]['message']['content'];
            $tokensUsed = $data['usage']['total_tokens'] ?? null;

            return [
                'success' => true,
                'message' => $aiMessage,
                'model_used' => $this->model,
                'tokens_used' => $tokensUsed,
            ];

        } catch (\Exception $e) {
            Log::error('AI Assistant Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Sorry, I encountered an error. Please try again.',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function buildConversationContext(string $userMessage, ?VirtualAssistantConversation $conversation, ?User $user): array
    {
        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt],
        ];

        // Add user context if available
        if ($user) {
            $userContext = $this->getUserContext($user);
            if ($userContext) {
                $messages[] = ['role' => 'system', 'content' => $userContext];
            }
        }

        // Add conversation history if available
        if ($conversation) {
            $recentMessages = $conversation->messages()
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->reverse();

            foreach ($recentMessages as $msg) {
                $role = $msg->sender === 'assistant' ? 'assistant' : 'user';
                $messages[] = ['role' => $role, 'content' => $msg->message];
            }
        }

        // Add current message
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        return $messages;
    }

    private function getUserContext(User $user): string
    {
        $context = "User Information:\n";
        $context .= "- Name: {$user->first_name} {$user->last_name}\n";
        $context .= "- Email: {$user->email}\n";
        $context .= "- Role: {$user->role}\n";

        if ($user->role === 'renter') {
            $context .= "- This user is a renter looking for properties.\n";
        } elseif ($user->role === 'landlord') {
            $context .= "- This user is a landlord listing properties.\n";
        }

        return $context;
    }

    public function createConversation(int $userId, string $channel = 'chat', ?string $phoneNumber = null): VirtualAssistantConversation
    {
        return VirtualAssistantConversation::create([
            'user_id' => $userId,
            'channel' => $channel,
            'phone_number' => $phoneNumber,
            'status' => 'active',
        ]);
    }

    public function saveMessage(int $conversationId, string $sender, string $message, bool $isAiGenerated = false, ?string $modelUsed = null, ?int $tokensUsed = null): VirtualAssistantMessage
    {
        return VirtualAssistantMessage::create([
            'conversation_id' => $conversationId,
            'sender' => $sender,
            'message' => $message,
            'is_ai_generated' => $isAiGenerated,
            'model_used' => $modelUsed,
            'tokens_used' => $tokensUsed,
        ]);
    }

    public function getConversationHistory(int $conversationId, int $limit = 50): array
    {
        $conversation = VirtualAssistantConversation::with('messages')
            ->findOrFail($conversationId);

        return [
            'conversation' => $conversation,
            'messages' => $conversation->messages()->orderBy('created_at', 'asc')->limit($limit)->get(),
        ];
    }

    public function isWithinBusinessHours(): bool
    {
        $startTime = VirtualAssistantSetting::get('business_hours_start', '09:00');
        $endTime = VirtualAssistantSetting::get('business_hours_end', '18:00');
        
        $now = now();
        $currentHour = $now->format('H:i');

        return $currentHour >= $startTime && $currentHour <= $endTime;
    }

    public function getBusinessHoursMessage(): string
    {
        $startTime = VirtualAssistantSetting::get('business_hours_start', '09:00');
        $endTime = VirtualAssistantSetting::get('business_hours_end', '18:00');

        return "Our business hours are from {$startTime} to {$endTime}. Outside these hours, responses may be delayed.";
    }
}
