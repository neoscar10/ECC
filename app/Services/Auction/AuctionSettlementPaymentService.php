<?php

namespace App\Services\Auction;

use App\Models\Payment;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class AuctionSettlementPaymentService
{
    /**
     * Finalize paid auction settlement order.
     */
    public function finalizePaidAuctionSettlement(Payment $payment): ?Order
    {
        if ($payment->payable_type !== Order::class) {
            Log::warning("AuctionSettlementPaymentService: Payment is not for Order", [
                'payment_id' => $payment->id,
                'payable_type' => $payment->payable_type,
            ]);
            return null;
        }

        /** @var Order $order */
        $order = $payment->payable;
        if (!$order) {
            Log::error("AuctionSettlementPaymentService: Order not found for payment", [
                'payment_id' => $payment->id,
            ]);
            return null;
        }

        // Just update columns to mark it paid
        $order->update([
            'payment_method' => $payment->gateway ?: 'razorpay',
            'payment_reference' => $payment->gateway_payment_id,
            'paid_at' => $payment->paid_at ?? now(),
        ]);

        Log::info("AuctionSettlementPaymentService: Auction settlement payment finalized (placeholder).", [
            'payment_id' => $payment->id,
            'order_id' => $order->id,
        ]);

        return $order;
    }

    /**
     * Handle failed payment for auction settlement.
     */
    public function markPaymentFailedAuctionSettlement(Payment $payment, string $reason): ?Order
    {
        if ($payment->payable_type !== Order::class) {
            return null;
        }

        /** @var Order $order */
        $order = $payment->payable;
        if (!$order) {
            return null;
        }

        Log::info("AuctionSettlementPaymentService: Auction settlement payment failed.", [
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'reason' => $reason,
        ]);

        return $order;
    }
}
