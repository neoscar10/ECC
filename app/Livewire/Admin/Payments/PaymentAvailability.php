<?php

namespace App\Livewire\Admin\Payments;

use App\Models\PaymentGateway;
use App\Models\PaymentGatewayPurpose;
use App\Services\Payments\PaymentSettingsService;
use Livewire\Component;

class PaymentAvailability extends Component
{
    public array $purposesList = [
        'shop_order' => 'Shop Order',
        'membership' => 'Membership',
        'vault_delivery' => 'Vault Delivery',
        'auction_settlement' => 'Auction Settlement',
    ];

    public function togglePurpose($gatewayId, $purpose)
    {
        $gateway = PaymentGateway::findOrFail($gatewayId);
        
        $mapping = PaymentGatewayPurpose::where('gateway_id', $gateway->id)
            ->where('purpose', $purpose)
            ->first();

        if ($mapping) {
            $old = ['is_enabled' => $mapping->is_enabled];
            $mapping->is_enabled = !$mapping->is_enabled;
            $mapping->save();
        } else {
            $old = ['is_enabled' => false];
            $mapping = PaymentGatewayPurpose::create([
                'gateway_id' => $gateway->id,
                'purpose' => $purpose,
                'is_enabled' => true,
            ]);
        }

        app(PaymentSettingsService::class)->auditChange(
            action: 'toggle_purpose_availability',
            entityType: PaymentGatewayPurpose::class,
            entityId: $mapping->id,
            oldValue: $old,
            newValue: ['is_enabled' => $mapping->is_enabled, 'gateway' => $gateway->code, 'purpose' => $purpose]
        );

        session()->flash('success', 'Purpose availability updated.');
    }

    public function render()
    {
        $gateways = PaymentGateway::orderBy('display_order', 'asc')->get();
        return view('livewire.admin.payments.payment-availability', [
            'gateways' => $gateways
        ])->layout('layouts.admin');
    }
}
