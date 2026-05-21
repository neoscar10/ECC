<?php

namespace App\Services\Payments\DTO;

class PaymentResult
{
    public bool $success;
    public string $status;
    public ?string $gateway;
    public ?string $gatewayOrderId;
    public ?string $gatewayPaymentId;
    public ?string $failureCode;
    public ?string $failureMessage;
    public array $checkout;
    public array $raw;

    public function __construct(
        bool $success,
        string $status,
        ?string $gateway = null,
        ?string $gatewayOrderId = null,
        ?string $gatewayPaymentId = null,
        ?string $failureCode = null,
        ?string $failureMessage = null,
        array $checkout = [],
        array $raw = []
    ) {
        $this->success = $success;
        $this->status = $status;
        $this->gateway = $gateway;
        $this->gatewayOrderId = $gatewayOrderId;
        $this->gatewayPaymentId = $gatewayPaymentId;
        $this->failureCode = $failureCode;
        $this->failureMessage = $failureMessage;
        $this->checkout = $checkout;
        $this->raw = $raw;
    }

    /**
     * Helper to build successful payment result.
     */
    public static function success(
        string $status,
        ?string $gateway = null,
        ?string $gatewayOrderId = null,
        ?string $gatewayPaymentId = null,
        array $checkout = [],
        array $raw = []
    ): self {
        return new self(
            success: true,
            status: $status,
            gateway: $gateway,
            gatewayOrderId: $gatewayOrderId,
            gatewayPaymentId: $gatewayPaymentId,
            checkout: $checkout,
            raw: $raw
        );
    }

    /**
     * Helper to build failed payment result.
     */
    public static function failed(
        string $status,
        ?string $failureCode = null,
        ?string $failureMessage = null,
        ?string $gateway = null,
        ?string $gatewayOrderId = null,
        ?string $gatewayPaymentId = null,
        array $raw = []
    ): self {
        return new self(
            success: false,
            status: $status,
            gateway: $gateway,
            gatewayOrderId: $gatewayOrderId,
            gatewayPaymentId: $gatewayPaymentId,
            failureCode: $failureCode,
            failureMessage: $failureMessage,
            raw: $raw
        );
    }

    /**
     * Helper to build pending payment result.
     */
    public static function pending(
        string $status,
        ?string $gateway = null,
        ?string $gatewayOrderId = null,
        array $checkout = [],
        array $raw = []
    ): self {
        return new self(
            success: false, // Pending is not yet fully successful (settled)
            status: $status,
            gateway: $gateway,
            gatewayOrderId: $gatewayOrderId,
            checkout: $checkout,
            raw: $raw
        );
    }

    /**
     * Convert the DTO to an array representation.
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'status' => $this->status,
            'gateway' => $this->gateway,
            'gateway_order_id' => $this->gatewayOrderId,
            'gateway_payment_id' => $this->gatewayPaymentId,
            'failure_code' => $this->failureCode,
            'failure_message' => $this->failureMessage,
            'checkout' => $this->checkout,
            'raw' => $this->raw,
        ];
    }
}
