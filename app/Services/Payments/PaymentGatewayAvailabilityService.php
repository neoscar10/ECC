<?php

namespace App\Services\Payments;

use App\Exceptions\PaymentGatewayValidationException;

class PaymentGatewayAvailabilityService
{
    /**
     * Get the default gateway from config.
     */
    public function defaultGateway(): string
    {
        return config('payments.default_gateway', 'razorpay');
    }

    /**
     * Get list of enabled payment gateway identifiers.
     */
    public function enabledGateways(): array
    {
        $gateways = [];
        $supported = config('payments.supported_gateways', []);
        foreach ($supported as $gateway) {
            if ($this->isEnabled($gateway)) {
                $gateways[] = $gateway;
            }
        }
        return $gateways;
    }

    /**
     * Check if a payment gateway is enabled.
     */
    public function isEnabled(string $gateway): bool
    {
        return (bool) config("payments.gateways.{$gateway}.enabled", false);
    }

    /**
     * Validate the selected payment gateway.
     * Returns the validated gateway identifier string.
     *
     * @throws PaymentGatewayValidationException
     */
    public function validateGateway(?string $gateway): string
    {
        if (is_null($gateway) || $gateway === '') {
            $gateway = $this->defaultGateway();
        }

        $gateway = strtolower($gateway);
        $supported = config('payments.supported_gateways', []);

        if (!in_array($gateway, $supported)) {
            throw new PaymentGatewayValidationException(
                'Invalid payment gateway selected.',
                [
                    'payment_gateway' => ['Invalid payment gateway selected.']
                ],
                422
            );
        }

        if (!$this->isEnabled($gateway)) {
            $label = $gateway === 'cashfree' ? 'Cashfree' : ucfirst($gateway);
            throw new PaymentGatewayValidationException(
                'Selected payment gateway is not available.',
                [
                    'payment_gateway' => ["{$label} is not available for checkout yet."]
                ],
                422
            );
        }

        // Verify driver class exists and implements PaymentGatewayInterface
        $driverClass = config("payments.gateways.{$gateway}.driver");
        if (empty($driverClass) || !class_exists($driverClass)) {
            throw new PaymentGatewayValidationException(
                'Selected payment gateway is not available.',
                [
                    'payment_gateway' => ["Payment gateway driver for {$gateway} is missing/unimplemented."]
                ],
                422
            );
        }

        return $gateway;
    }

    /**
     * Get public gateway options with labels, descriptions, and availability.
     */
    public function publicOptions(): array
    {
        $options = [];
        $supported = config('payments.supported_gateways', []);
        foreach ($supported as $gateway) {
            $config = config("payments.gateways.{$gateway}", []);
            $enabled = (bool) ($config['enabled'] ?? false);

            if ($gateway === 'razorpay') {
                $label = 'Razorpay';
                $description = 'Pay using UPI, cards, netbanking and wallets.';
            } elseif ($gateway === 'cashfree') {
                $label = 'Cashfree';
                $description = 'Pay using Cashfree supported methods.';
            } else {
                $label = ucfirst($gateway);
                $description = 'Pay using ' . $label . ' supported methods.';
            }

            $options[] = [
                'key' => $gateway,
                'label' => $label,
                'description' => $description,
                'enabled' => $enabled,
            ];
        }

        return $options;
    }
}
