<?php

namespace App\Services\Shop;

use App\Models\Payment;
use App\Models\Shop\ShopOrder;
use Illuminate\Support\Facades\Log;

class OrderPaymentService
{
    protected CheckoutService $checkoutService;

    public function __construct(CheckoutService $checkoutService)
    {
        $this->checkoutService = $checkoutService;
    }

    /**
     * Finalize the order after a successful payment.
     *
     * @param Payment $payment
     * @return ShopOrder|null
     */
    public function finalizePaidOrder(Payment $payment): ?ShopOrder
    {
        if ($payment->payable_type !== ShopOrder::class) {
            Log::warning("OrderPaymentService: Attempted to finalize payment for non-order entity.", [
                'payment_id' => $payment->id,
                'payable_type' => $payment->payable_type,
            ]);
            return null;
        }

        /** @var ShopOrder $order */
        $order = $payment->payable;

        if (!$order) {
            Log::error("OrderPaymentService: Order not found for payment.", [
                'payment_id' => $payment->id,
            ]);
            return null;
        }

        $paymentDetails = [
            'gateway' => $payment->gateway,
            'payment_id' => $payment->id,
            'gateway_payment_id' => $payment->gateway_payment_id,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
        ];

        // The confirmPayment method ensures idempotency by checking if already paid.
        return $this->checkoutService->confirmPayment($order, $paymentDetails);
    }

    /**
     * Handle order state when a payment fails.
     *
     * @param Payment $payment
     * @param string $reason
     * @return ShopOrder|null
     */
    public function markPaymentFailedOrder(Payment $payment, string $reason): ?ShopOrder
    {
        if ($payment->payable_type !== ShopOrder::class) {
            return null;
        }

        /** @var ShopOrder $order */
        $order = $payment->payable;

        if (!$order) {
            return null;
        }

        // Keep order in pending_payment status but log the failure
        Log::info("OrderPaymentService: Payment failed for order.", [
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'reason' => $reason,
        ]);

        return $order;
    }
}
