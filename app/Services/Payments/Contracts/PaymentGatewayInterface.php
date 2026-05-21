<?php

namespace App\Services\Payments\Contracts;

use App\Models\Payment;
use App\Services\Payments\DTO\PaymentResult;
use App\Services\Payments\DTO\PaymentVerificationData;

interface PaymentGatewayInterface
{
    /**
     * Get the payment gateway identifier name.
     */
    public function gatewayName(): string;

    /**
     * Create an order on the gateway.
     */
    public function createOrder(Payment $payment, array $context = []): PaymentResult;

    /**
     * Verify payment authenticity using signature/payload.
     */
    public function verifyPayment(Payment $payment, PaymentVerificationData $data): PaymentResult;

    /**
     * Fetch payment status and details from the gateway.
     */
    public function fetchPayment(string $gatewayPaymentId): PaymentResult;

    /**
     * Handle incoming gateway webhook logic.
     */
    public function handleWebhook(array $payload, ?string $signature = null, ?string $rawBody = null): PaymentResult;
}
