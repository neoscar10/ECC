<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RazorpayWebhookController extends Controller
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
     * Handle incoming Razorpay webhook payload.
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $payload = $request->json()->all();
            $signature = $request->header('X-Razorpay-Signature');
            $rawBody = $request->getContent();

            $result = $this->webhookService->handle('razorpay', $payload, $signature, $rawBody);

            if (isset($result['signature_valid']) && !$result['signature_valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid webhook signature.',
                    'data' => $result
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully.',
                'data' => $result
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
