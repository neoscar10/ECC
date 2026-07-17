<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\OtpVerification;
use App\Services\Otp\PhoneNormalizer;
use App\Services\Otp\Delivery\MetaWhatsAppService;

class WhatsAppWebhookController extends Controller
{
    /**
     * Handle Meta webhook verification challenge.
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $verifyToken = config('services.whatsapp.webhook_verify_token');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('WhatsApp Webhook Verified Successfully.');
            return response($challenge, 200);
        }

        Log::warning('WhatsApp Webhook Verification Failed.', [
            'received_token' => $token,
            'expected_token' => $verifyToken
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Handle incoming WhatsApp messages.
     */
    public function handle(Request $request, PhoneNormalizer $normalizer, MetaWhatsAppService $whatsappService)
    {
        try {
            $payload = $request->all();

            // Meta sends a POST request with the 'object' set to 'whatsapp_business_account'
            if (($payload['object'] ?? '') !== 'whatsapp_business_account') {
                return response('OK', 200);
            }

            foreach ($payload['entry'] ?? [] as $entry) {
                foreach ($entry['changes'] ?? [] as $change) {
                    $value = $change['value'] ?? [];
                    
                    // We only care about incoming messages
                    if (!isset($value['messages']) || empty($value['messages'])) {
                        continue;
                    }

                    foreach ($value['messages'] as $message) {
                        // We only process text messages
                        if (($message['type'] ?? '') !== 'text') {
                            continue;
                        }

                        $fromNumber = $message['from'] ?? null;
                        if (!$fromNumber) {
                            continue;
                        }

                        // The 'from' number is typically E.164 without the '+'.
                        // Normalize it to our standard internal format (e.g., +91...)
                        $phone = '+' . ltrim($fromNumber, '+');
                        $normalizedPhone = $normalizer->normalize($phone);

                        Log::info('WhatsApp Webhook: Received message.', [
                            'from' => substr($fromNumber, -4),
                            'text' => $message['text']['body'] ?? ''
                        ]);

                        $this->processIncomingOtpRequest($normalizedPhone, $whatsappService);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp Webhook: Exception during processing.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Always return 200 OK so Meta doesn't retry
        return response('OK', 200);
    }

    /**
     * Process an incoming message to send an OTP if pending.
     */
    private function processIncomingOtpRequest(string $phone, MetaWhatsAppService $whatsappService)
    {
        // Check if there is a pending plaintext OTP in the cache for this phone
        $otpPlaintext = \Illuminate\Support\Facades\Cache::get('otp_plaintext_' . $phone);

        if (!$otpPlaintext) {
            Log::info('WhatsApp Webhook: No pending OTP found in cache.', ['phone_last4' => substr($phone, -4)]);
            $whatsappService->sendRawMessage($phone, "No pending OTP request found for this number. Please initiate a request from the website.");
            return;
        }

        // Send the OTP and clear it from the cache
        $whatsappService->sendRawMessage($phone, "Your Executive Cricket Club verification OTP is: {$otpPlaintext}. Valid for 5 minutes.");
        \Illuminate\Support\Facades\Cache::forget('otp_plaintext_' . $phone);
        
        Log::info('WhatsApp Webhook: OTP sent via direct message.', ['phone_last4' => substr($phone, -4)]);
    }
}
