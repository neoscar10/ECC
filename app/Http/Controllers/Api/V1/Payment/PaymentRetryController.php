<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\PaymentManager;
use App\Services\Payments\PaymentGatewayAvailabilityService;
use App\Http\Resources\Payment\PaymentResource;
use App\Support\ApiResponse;
use App\Support\Payments\PaymentStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class PaymentRetryController extends Controller
{
    use ApiResponse;

    protected PaymentManager $paymentManager;
    protected PaymentGatewayAvailabilityService $availabilityService;

    public function __construct(
        PaymentManager $paymentManager,
        PaymentGatewayAvailabilityService $availabilityService
    ) {
        $this->paymentManager = $paymentManager;
        $this->availabilityService = $availabilityService;
    }

    /**
     * Re-initiate payment attempt using the same gateway and payable.
     */
    public function retry(Request $request, $id): JsonResponse
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return $this->error('Payment not found.', 404);
        }

        $user = $request->user();

        // Validate ownership
        if ($payment->user_id !== $user->id) {
            return $this->error('Unauthorized access to payment.', 403);
        }

        // Cannot retry paid payments
        if ($payment->status === PaymentStatus::PAID) {
            return $this->error('Cannot retry a completed payment.', 400);
        }

        // Cannot retry processing/pending payments
        if ($payment->status === PaymentStatus::PENDING || $payment->status === PaymentStatus::INITIATED) {
            return $this->error('Cannot retry a payment that is still processing/pending.', 400);
        }

        $payable = $payment->payable;

        // If the payable/order/request is already paid, prevent retry
        if ($payable && isset($payable->payment_status) && in_array($payable->payment_status, ['paid', 'test_paid'])) {
            return $this->error('The associated order or application has already been paid.', 400);
        }

        $gateway = $payment->gateway ?: config('payments.default_gateway', 'razorpay');

        try {
            $gateway = $this->availabilityService->validateGateway($gateway, $payment->purpose);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            // Create a new Payment record/attempt using PaymentManager
            $paymentInitiation = $this->paymentManager->initiatePayment(
                payable: $payable,
                amount: $payment->amount,
                purpose: $payment->purpose ?: 'shop_order',
                user: $user,
                gateway: $gateway
            );

            return $this->success(
                new PaymentResource($paymentInitiation['payment']),
                'Payment retry initiated successfully.'
            );
        } catch (Exception $e) {
            return $this->error('Could not retry payment: ' . $e->getMessage(), 500);
        }
    }
}
