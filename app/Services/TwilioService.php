<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwilioService
{
    private string $accountSid;
    private string $authToken;
    private string $twilioNumber;
    private string $apiKeySid;
    private string $apiKeySecret;

    public function __construct()
    {
        $this->accountSid = env('TWILIO_ACCOUNT_SID', '');
        $this->authToken = env('TWILIO_AUTH_TOKEN', '');
        $this->twilioNumber = env('TWILIO_PHONE_NUMBER', '');
        $this->apiKeySid = env('TWILIO_API_KEY_SID', '');
        $this->apiKeySecret = env('TWILIO_API_KEY_SECRET', '');
    }

    public function sendSMS(string $to, string $message): array
    {
        try {
            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json", [
                    'From' => $this->twilioNumber,
                    'To' => $to,
                    'Body' => $message,
                ]);

            if (!$response->successful()) {
                Log::error('Twilio SMS Error: ' . $response->body());
                return [
                    'success' => false,
                    'error' => $response->body(),
                ];
            }

            return [
                'success' => true,
                'message_sid' => $response->json('sid'),
            ];

        } catch (\Exception $e) {
            Log::error('Twilio SMS Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function initiateCall(string $to, string $webhookUrl): array
    {
        try {
            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Calls.json", [
                    'From' => $this->twilioNumber,
                    'To' => $to,
                    'Url' => $webhookUrl,
                    'Method' => 'POST',
                ]);

            if (!$response->successful()) {
                Log::error('Twilio Call Error: ' . $response->body());
                return [
                    'success' => false,
                    'error' => $response->body(),
                ];
            }

            return [
                'success' => true,
                'call_sid' => $response->json('sid'),
            ];

        } catch (\Exception $e) {
            Log::error('Twilio Call Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function generateTwiML(string $message, bool $gatherInput = false): string
    {
        $twiml = '<?xml version="1.0" encoding="UTF-8"?>';
        $twiml .= '<Response>';
        
        if ($gatherInput) {
            $twiml .= '<Gather action="/api/virtual-assistant/twilio/gather" method="POST" input="speech" timeout="5" language="fr-CA">';
            $twiml .= '<Say language="fr-CA">' . htmlspecialchars($message) . '</Say>';
            $twiml .= '</Gather>';
            $twiml .= '<Say language="fr-CA">Je n\'ai pas entendu votre réponse. Veuillez réessayer.</Say>';
        } else {
            $twiml .= '<Say language="fr-CA">' . htmlspecialchars($message) . '</Say>';
        }
        
        $twiml .= '</Response>';
        
        return $twiml;
    }

    public function textToSpeech(string $text): array
    {
        try {
            $response = Http::withBasicAuth($this->apiKeySid, $this->apiKeySecret)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Calls.json", [
                    'From' => $this->twilioNumber,
                    'To' => env('TEST_PHONE_NUMBER', ''),
                    'Url' => route('twilio.tts', ['text' => urlencode($text)]),
                    'Method' => 'POST',
                ]);

            if (!$response->successful()) {
                Log::error('Twilio TTS Error: ' . $response->body());
                return [
                    'success' => false,
                    'error' => $response->body(),
                ];
            }

            return [
                'success' => true,
                'call_sid' => $response->json('sid'),
            ];

        } catch (\Exception $e) {
            Log::error('Twilio TTS Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function validatePhoneNumber(string $phoneNumber): bool
    {
        // Basic validation for Canadian phone numbers
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Canadian numbers: 10 digits starting with area code
        return (bool) preg_match('/^[2-9][0-9]{9}$/', $phoneNumber);
    }

    public function formatPhoneNumber(string $phoneNumber): string
    {
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        if (strlen($phoneNumber) === 10) {
            return '+1' . $phoneNumber;
        }
        
        if (strlen($phoneNumber) === 11 && $phoneNumber[0] === '1') {
            return '+' . $phoneNumber;
        }
        
        return $phoneNumber;
    }
}
