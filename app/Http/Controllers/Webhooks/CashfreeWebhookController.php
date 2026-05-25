<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashfreeWebhookController extends Controller
{
    protected PaymentWebhookService $webhookService;

    /**
     * Create a new controller instance.
     */
    public function __construct(PaymentWebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Handle incoming Cashfree webhook payload.
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $payload = $request->json()->all();
            if (empty($payload)) {
                $payload = $request->all();
            }

            $signature = $request->header('x-webhook-signature')
                ?? $request->header('x-cf-signature')
                ?? $request->header('x-cashfree-signature');
            
            $timestamp = $request->header('x-webhook-timestamp');
            $rawBody = $request->getContent();

            // Format signature with timestamp if present
            $signatureParam = $timestamp ? "{$timestamp}:{$signature}" : $signature;

            $result = $this->webhookService->handle('cashfree', $payload, $signatureParam, $rawBody);

            if (isset($result['signature_valid']) && !$result['signature_valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid webhook signature.',
                ], 400);
            }

            if (isset($result['status']) && $result['status'] === 'duplicate') {
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook already processed.',
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed.'
            ], 500);
        }
    }
}
