<?php

namespace App\Livewire\Admin\Payments;

use App\Models\PaymentGateway;
use App\Services\Payments\PaymentSettingsService;
use Livewire\Component;

class Gateways extends Component
{
    public function toggleEnabled($gatewayId)
    {
        $gateway = PaymentGateway::findOrFail($gatewayId);

        // Validation: Prevent disabling all gateways or removing the last active gateway
        $activeCount = PaymentGateway::where('is_enabled', true)->count();
        if ($gateway->is_enabled && $activeCount <= 1) {
            session()->flash('error', 'Cannot disable the last active payment gateway.');
            return;
        }

        $old = ['is_enabled' => $gateway->is_enabled, 'is_visible_to_users' => $gateway->is_visible_to_users];
        $gateway->is_enabled = !$gateway->is_enabled;
        $gateway->is_visible_to_users = $gateway->is_enabled;
        
        // If default and disabling, throw error
        if ($gateway->is_default && !$gateway->is_enabled) {
            session()->flash('error', 'Cannot disable the default payment gateway.');
            return;
        }

        $gateway->save();

        app(PaymentSettingsService::class)->auditChange(
            action: 'toggle_gateway_status',
            entityType: PaymentGateway::class,
            entityId: $gateway->id,
            oldValue: $old,
            newValue: ['is_enabled' => $gateway->is_enabled, 'is_visible_to_users' => $gateway->is_visible_to_users]
        );

        session()->flash('success', $gateway->name . ' status updated.');
    }

    public function makeDefault($gatewayId)
    {
        $gateway = PaymentGateway::findOrFail($gatewayId);

        // Validation: Prevent making disabled gateway default
        if (!$gateway->is_enabled) {
            session()->flash('error', 'Cannot make a disabled gateway the default.');
            return;
        }

        $oldDefault = PaymentGateway::where('is_default', true)->first();
        $oldDefaultId = $oldDefault ? $oldDefault->id : null;

        PaymentGateway::where('is_default', true)->update(['is_default' => false]);
        
        $gateway->is_default = true;
        $gateway->save();

        app(PaymentSettingsService::class)->auditChange(
            action: 'set_default_gateway',
            entityType: PaymentGateway::class,
            entityId: $gateway->id,
            oldValue: ['old_default_id' => $oldDefaultId],
            newValue: ['new_default_id' => $gateway->id]
        );

        session()->flash('success', $gateway->name . ' set as default gateway.');
    }

    public function moveUp($gatewayId)
    {
        $gateway = PaymentGateway::findOrFail($gatewayId);
        $previous = PaymentGateway::where('display_order', '<', $gateway->display_order)
            ->orderBy('display_order', 'desc')
            ->first();

        if ($previous) {
            $oldOrder = $gateway->display_order;
            $gateway->display_order = $previous->display_order;
            $previous->display_order = $oldOrder;
            
            $gateway->save();
            $previous->save();
        }
    }

    public function moveDown($gatewayId)
    {
        $gateway = PaymentGateway::findOrFail($gatewayId);
        $next = PaymentGateway::where('display_order', '>', $gateway->display_order)
            ->orderBy('display_order', 'asc')
            ->first();

        if ($next) {
            $oldOrder = $gateway->display_order;
            $gateway->display_order = $next->display_order;
            $next->display_order = $oldOrder;
            
            $gateway->save();
            $next->save();
        }
    }

    public function render()
    {
        $gateways = PaymentGateway::orderBy('display_order', 'asc')->get();
        return view('livewire.admin.payments.gateways', [
            'gateways' => $gateways
        ])->layout('layouts.admin');
    }
}
