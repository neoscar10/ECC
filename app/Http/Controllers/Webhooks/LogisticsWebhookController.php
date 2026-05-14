<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Shipping\ShipmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogisticsWebhookController extends Controller
{
    protected $shipmentService;

    public function __construct(ShipmentService $shipmentService)
    {
        $this->shipmentService = $shipmentService;
    }

    /**
     * Webhook health check.
     */
    public function health()
    {
        return response()->json([
            'success' => true,
            'message' => 'Logistics webhook endpoint is reachable.',
            'webhook_url' => config('shiprocket.webhook_url'),
            'token_configured' => filled(config('shiprocket.webhook_token')),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Handle incoming tracking updates from logistics provider.
     */
    public function tracking(Request $request, \App\Services\Shipping\Shiprocket\ShiprocketTrackingWebhookService $webhookService)
    {
        $token = config('shiprocket.webhook_token');
        $incomingToken = $request->header('x-api-key');

        if (empty($token)) {
            // If running in production and token is not configured, we should reject it for safety.
            if (!app()->environment('local', 'testing')) {
                Log::error('Logistics Webhook: Token is not configured in production.');
                return response()->json(['success' => false, 'message' => 'Configuration error.'], 500);
            }
            Log::warning('Logistics Webhook: Missing token configuration but accepted due to local/testing environment.');
        } elseif (!hash_equals($token, (string) $incomingToken)) {
            Log::warning('Logistics Webhook: Unauthorized attempt', [
                'ip' => $request->ip(),
                'token_provided' => (bool) $incomingToken,
            ]);
            return response()->json(['success' => false, 'message' => 'Invalid webhook token.'], 401);
        }

        $payload = $request->all();
        
        Log::info('Logistics Webhook: Received payload', [
            'provider' => 'shiprocket',
            'payload' => $payload,
        ]);

        try {
            $result = $webhookService->handle($payload, $request->headers->all());

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Logistics Webhook: Failed to process payload.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Return 200 to prevent constant Shiprocket retries if payload was bad, but return success => false
            return response()->json([
                'success' => false,
                'message' => 'Internal processing error.',
            ], 200);
        }
    }
}
