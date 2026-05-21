<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Controller;
use App\Http\Resources\Shop\ShopOrderResource;
use App\Models\Payment;
use App\Services\Payments\PaymentManager;
use App\Services\Shop\OrderPaymentService;
use App\Support\ApiResponse;
use App\Support\Payments\PaymentStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RazorpayPaymentController extends Controller
{
    use ApiResponse;

    protected PaymentManager $paymentManager;
    protected OrderPaymentService $orderPaymentService;

    public function __construct(PaymentManager $paymentManager, OrderPaymentService $orderPaymentService)
    {
        $this->paymentManager = $paymentManager;
        $this->orderPaymentService = $orderPaymentService;
    }

    /**
     * Verify the Razorpay payment signature from Mobile App.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'payment_id' => 'required|exists:payments,id',
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $payment = Payment::findOrFail($request->input('payment_id'));

        // Ensure the authenticated user owns the payment
        if ($payment->user_id !== auth()->id()) {
            return $this->error('Unauthorized access to payment.', 403);
        }

        // If already paid, return success
        if ($payment->status === PaymentStatus::PAID) {
            return $this->buildSuccessResponse($payment);
        }

        try {
            $verificationData = [
                'gateway' => 'razorpay',
                'gateway_order_id' => $request->input('razorpay_order_id'),
                'gateway_payment_id' => $request->input('razorpay_payment_id'),
                'gateway_signature' => $request->input('razorpay_signature'),
                'payload' => $request->all(),
            ];

            $result = $this->paymentManager->verifyPayment($payment, $verificationData);

            if ($result['result']->success && $result['payment']->status === PaymentStatus::PAID) {
                // Finalize order
                $this->orderPaymentService->finalizePaidOrder($result['payment']);

                return $this->buildSuccessResponse($result['payment'], 'Payment verified successfully.');
            } else {
                $this->orderPaymentService->markPaymentFailedOrder($result['payment'], 'Verification failed');
                
                return $this->buildFailureResponse($result['payment'], 'Payment verification failed.');
            }
        } catch (\Exception $e) {
            Log::error('API Razorpay verification exception: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error('Internal Server Error during verification.', 500);
        }
    }

    /**
     * Build standard verification success response.
     */
    protected function buildSuccessResponse(Payment $payment, string $message = 'Payment already paid.'): JsonResponse
    {
        $payable = $payment->payable;

        $data = [
            'payment' => [
                'id' => $payment->id,
                'gateway' => $payment->gateway,
                'status' => $payment->status,
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'gateway_order_id' => $payment->gateway_order_id,
                'gateway_payment_id' => $payment->gateway_payment_id,
                'paid_at' => $payment->paid_at ? $payment->paid_at->toIso8601String() : null,
            ]
        ];

        if ($payable && $payment->payable_type === \App\Models\Shop\ShopOrder::class) {
            $data['order'] = new ShopOrderResource($payable);
        }

        return $this->success($data, $message);
    }

    /**
     * Build standard verification failure response.
     */
    protected function buildFailureResponse(Payment $payment, string $message): JsonResponse
    {
        $data = [
            'payment' => [
                'id' => $payment->id,
                'gateway' => $payment->gateway,
                'status' => $payment->status,
            ]
        ];

        return $this->error($message, 422, [
            'payment' => ['Invalid payment signature.']
        ]);
    }
}
