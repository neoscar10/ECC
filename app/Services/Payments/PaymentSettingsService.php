<?php

namespace App\Services\Payments;

use App\Models\PaymentGateway;
use App\Models\PaymentGatewayPurpose;
use App\Models\PaymentGatewayMethod;
use App\Models\PaymentSettingAudit;
use App\Exceptions\PaymentGatewayValidationException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class PaymentSettingsService
{
    /**
     * Helper to verify if the payment tables exist in database.
     */
    protected function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get enabled payment gateway identifiers.
     */
    public function getEnabledGateways(): array
    {
        if ($this->hasTable('payment_gateways') && PaymentGateway::count() > 0) {
            return PaymentGateway::where('is_enabled', true)
                ->orderBy('display_order', 'asc')
                ->pluck('code')
                ->toArray();
        }

        // Fallback
        $supported = config('payments.supported_gateways', []);
        return array_values(array_filter($supported, function ($gw) {
            return (bool) config("payments.gateways.{$gw}.enabled", false);
        }));
    }

    /**
    public function getVisibleGateways(): array
    {
        return $this->getEnabledGateways();
    }

    /**
     * Get the default payment gateway.
     */
    public function getDefaultGateway(): string
    {
        if ($this->hasTable('payment_gateways') && PaymentGateway::count() > 0) {
            $default = PaymentGateway::where('is_enabled', true)
                ->where('is_default', true)
                ->first();

            if ($default) {
                return $default->code;
            }

            // Fallback to first enabled gateway if no default marked
            $first = PaymentGateway::where('is_enabled', true)
                ->orderBy('display_order', 'asc')
                ->first();

            if ($first) {
                return $first->code;
            }
        }

        return config('payments.default_gateway', 'razorpay');
    }

    /**
     * Get enabled gateways for a specific purpose.
     */
    public function getGatewaysForPurpose(string $purpose): array
    {
        $purpose = $this->mapPurpose($purpose);

        if ($this->hasTable('payment_gateways') && $this->hasTable('payment_gateway_purposes') && PaymentGateway::count() > 0) {
            return PaymentGateway::where('is_enabled', true)
                ->where('is_visible_to_users', true)
                ->whereHas('purposes', function ($q) use ($purpose) {
                    $q->where('purpose', $purpose)->where('is_enabled', true);
                })
                ->orderBy('display_order', 'asc')
                ->pluck('code')
                ->toArray();
        }

        return $this->getEnabledGateways();
    }

    /**
     * Map payment model purpose to database mapped purpose.
     */
    public function mapPurpose(string $purpose): string
    {
        $purpose = strtolower($purpose);

        if ($purpose === 'membership_upgrade' || $purpose === 'membership_renewal') {
            return 'membership';
        }

        return $purpose;
    }

    /**
     * Check if a gateway is enabled.
     */
    public function isGatewayEnabled(string $gateway): bool
    {
        $gateway = strtolower($gateway);

        if ($this->hasTable('payment_gateways') && PaymentGateway::where('code', $gateway)->exists()) {
            return PaymentGateway::where('code', $gateway)
                ->where('is_enabled', true)
                ->exists();
        }

        return (bool) config("payments.gateways.{$gateway}.enabled", false);
    }

    /**
     * Check if a gateway is allowed for a purpose.
     */
    public function isGatewayAllowedForPurpose(string $gateway, string $purpose): bool
    {
        $gateway = strtolower($gateway);
        $mappedPurpose = $this->mapPurpose($purpose);

        if ($this->hasTable('payment_gateways') && $this->hasTable('payment_gateway_purposes') && PaymentGateway::where('code', $gateway)->exists()) {
            $gatewayModel = PaymentGateway::where('code', $gateway)->first();
            if (!$gatewayModel) {
                return false;
            }

            // If purpose record is missing in DB, allow by default if gateway exists
            $purposeExists = PaymentGatewayPurpose::where('gateway_id', $gatewayModel->id)
                ->where('purpose', $mappedPurpose)
                ->exists();
            if (!$purposeExists) {
                return true;
            }

            return PaymentGatewayPurpose::where('gateway_id', $gatewayModel->id)
                ->where('purpose', $mappedPurpose)
                ->where('is_enabled', true)
                ->exists();
        }

        return true; // Fallback to true if DB empty/unseeded
    }

    /**
     * Check if a specific payment method is allowed for a gateway.
     */
    public function isMethodAllowed(string $gateway, string $method): bool
    {
        $gateway = strtolower($gateway);
        $method = strtolower($method);

        if ($this->hasTable('payment_gateways') && $this->hasTable('payment_gateway_methods') && PaymentGateway::where('code', $gateway)->exists()) {
            $gatewayModel = PaymentGateway::where('code', $gateway)->first();
            if (!$gatewayModel) {
                return false;
            }

            // If method record is missing in DB, allow by default if gateway exists
            $methodExists = PaymentGatewayMethod::where('gateway_id', $gatewayModel->id)
                ->where('method', $method)
                ->exists();
            if (!$methodExists) {
                return true;
            }

            return PaymentGatewayMethod::where('gateway_id', $gatewayModel->id)
                ->where('method', $method)
                ->where('is_enabled', true)
                ->exists();
        }

        return true; // Fallback to true if DB empty/unseeded
    }

    /**
     * Get display order of gateways.
     */
    public function getGatewayDisplayOrder(): array
    {
        if ($this->hasTable('payment_gateways') && PaymentGateway::count() > 0) {
            return PaymentGateway::orderBy('display_order', 'asc')
                ->pluck('code')
                ->toArray();
        }

        return config('payments.supported_gateways', []);
    }

    /**
     * Validate the selected payment gateway and purpose.
     *
     * @throws PaymentGatewayValidationException
     */
    public function validateGatewaySelection(?string $gateway, ?string $purpose = null): string
    {
        if (is_null($gateway) || $gateway === '') {
            $gateway = $this->getDefaultGateway();
        }

        $gateway = strtolower($gateway);

        // A. Basic support check
        $supported = config('payments.supported_gateways', []);
        if (!in_array($gateway, $supported)) {
            throw new PaymentGatewayValidationException(
                'Invalid payment gateway selected.',
                ['payment_gateway' => ['Invalid payment gateway selected.']],
                422
            );
        }

        // B. Enabled check
        if (!$this->isGatewayEnabled($gateway)) {
            $label = $gateway === 'cashfree' ? 'Cashfree' : ucfirst($gateway);
            throw new PaymentGatewayValidationException(
                'Selected payment gateway is not available.',
                ['payment_gateway' => ["{$label} is not available for checkout yet."]],
                422
            );
        }

        // D. Purpose restriction check
        if ($purpose) {
            if (!$this->isGatewayAllowedForPurpose($gateway, $purpose)) {
                throw new PaymentGatewayValidationException(
                    'Selected payment gateway is not available for this payment type.',
                    ['payment_gateway' => ["The selected gateway is not authorized for this payment type."]],
                    422
                );
            }
        }

        return $gateway;
    }

    /**
     * Log an admin payment configuration change.
     */
    public function auditChange(string $action, ?string $entityType = null, ?int $entityId = null, ?array $oldValue = null, ?array $newValue = null): PaymentSettingAudit
    {
        return PaymentSettingAudit::create([
            'admin_user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
