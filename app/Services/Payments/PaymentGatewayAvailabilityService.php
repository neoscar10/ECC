<?php

namespace App\Services\Payments;

use App\Exceptions\PaymentGatewayValidationException;
use App\Models\PaymentGateway;

class PaymentGatewayAvailabilityService
{
    protected PaymentSettingsService $settingsService;

    public function __construct(?PaymentSettingsService $settingsService = null)
    {
        $this->settingsService = $settingsService ?: app(PaymentSettingsService::class);
    }

    /**
     * Get the default gateway from settings.
     */
    public function defaultGateway(): string
    {
        return $this->settingsService->getDefaultGateway();
    }

    /**
     * Get list of enabled payment gateway identifiers.
     */
    public function enabledGateways(): array
    {
        return $this->settingsService->getEnabledGateways();
    }

    /**
     * Check if a payment gateway is enabled.
     */
    public function isEnabled(string $gateway): bool
    {
        return $this->settingsService->isGatewayEnabled($gateway);
    }

    /**
     * Validate the selected payment gateway.
     * Returns the validated gateway identifier string.
     *
     * @throws PaymentGatewayValidationException
     */
    public function validateGateway(?string $gateway, ?string $purpose = null): string
    {
        return $this->settingsService->validateGatewaySelection($gateway, $purpose);
    }

    /**
     * Get public gateway options with labels, descriptions, and availability.
     */
    public function publicOptions(?string $purpose = null): array
    {
        $options = [];
        
        // Load display order dynamically from database if possible
        $gatewayCodes = $this->settingsService->getGatewayDisplayOrder();

        foreach ($gatewayCodes as $gateway) {
            // Find gateway settings in DB
            $dbGateway = PaymentGateway::where('code', $gateway)->first();
            
            if ($dbGateway) {
                $enabled = $dbGateway->is_enabled;
                
                // If a purpose is supplied, we only include the gateway if it's authorized for that purpose.
                // If the gateway is not allowed for the purpose, we treat it as disabled/hidden.
                if ($purpose && !$this->settingsService->isGatewayAllowedForPurpose($gateway, $purpose)) {
                    $enabled = false;
                }

                $visible = $enabled;
                $label = $dbGateway->name;
                $description = $dbGateway->description;
            } else {
                // Fallback to static config mapping
                $config = config("payments.gateways.{$gateway}", []);
                $enabled = (bool) ($config['enabled'] ?? false);
                $visible = true;

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
            }

            // Skip if not visible to users
            if (!$visible) {
                continue;
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
