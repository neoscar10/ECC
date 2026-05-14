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
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Handle incoming tracking updates from logistics provider.
     */
    public function tracking(Request $request)
    {
        $token = config('shiprocket.webhook_token');
        $incomingToken = $request->header('x-api-key');

        if (empty($token) || $incomingToken !== $token) {
            Log::warning('Logistics Webhook: Unauthorized attempt', [
                'ip' => $request->ip(),
                'token_provided' => (bool) $incomingToken,
            ]);
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        
        Log::info('Logistics Webhook: Received payload', [
            'provider' => 'shiprocket',
            'payload' => $payload,
        ]);

        // Attempt to find matching shipment and record event
        $awbCode = $payload['awb'] ?? $payload['awb_code'] ?? null;
        $orderId = $payload['order_id'] ?? null;
        $shipmentId = $payload['shipment_id'] ?? null;

        $shipment = $this->shipmentService->findByProviderReference($orderId, $shipmentId, $awbCode);

        if ($shipment) {
            $this->shipmentService->recordEvent($shipment, [
                'event_code' => $payload['current_status_id'] ?? null,
                'event_status' => $payload['current_status'] ?? null,
                'event_description' => $payload['status_description'] ?? null,
                'location' => $payload['last_location'] ?? null,
                'event_time' => $payload['current_timestamp'] ?? now(),
                'raw_payload' => $payload,
            ]);
        }

        return response()->json([
            'success' => true,
            'received_at' => now()->toIso8601String(),
        ]);
    }
}
