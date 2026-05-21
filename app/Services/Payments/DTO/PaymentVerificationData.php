<?php

namespace App\Services\Payments\DTO;

class PaymentVerificationData
{
    public string $gateway;
    public ?string $gatewayOrderId;
    public ?string $gatewayPaymentId;
    public ?string $gatewaySignature;
    public array $payload;

    public function __construct(
        string $gateway,
        ?string $gatewayOrderId = null,
        ?string $gatewayPaymentId = null,
        ?string $gatewaySignature = null,
        array $payload = []
    ) {
        $this->gateway = $gateway;
        $this->gatewayOrderId = $gatewayOrderId;
        $this->gatewayPaymentId = $gatewayPaymentId;
        $this->gatewaySignature = $gatewaySignature;
        $this->payload = $payload;
    }

    /**
     * Create a verification DTO from generic or gateway-specific input array.
     */
    public static function fromArray(array $data): self
    {
        $gateway = $data['gateway'] ?? '';
        
        // Auto-extract signature/order/payment IDs from various gateway payload shapes
        $orderId = $data['gateway_order_id'] ?? $data['razorpay_order_id'] ?? $data['cf_order_id'] ?? $data['order_id'] ?? null;
        $paymentId = $data['gateway_payment_id'] ?? $data['razorpay_payment_id'] ?? $data['cf_payment_id'] ?? $data['payment_id'] ?? null;
        $signature = $data['gateway_signature'] ?? $data['razorpay_signature'] ?? $data['cf_signature'] ?? $data['signature'] ?? null;

        return new self(
            gateway: $gateway,
            gatewayOrderId: $orderId,
            gatewayPaymentId: $paymentId,
            gatewaySignature: $signature,
            payload: $data['payload'] ?? $data
        );
    }
}
