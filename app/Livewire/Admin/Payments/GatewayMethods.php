<?php

namespace App\Livewire\Admin\Payments;

use App\Models\PaymentGateway;
use App\Models\PaymentGatewayMethod;
use App\Services\Payments\PaymentSettingsService;
use Livewire\Component;

class GatewayMethods extends Component
{
    public $selectedGatewayId = null;

    public array $methodsList = [
        'upi' => 'UPI',
        'cards' => 'Cards',
        'netbanking' => 'Net Banking',
        'wallets' => 'Wallets',
        'emi' => 'EMI',
        'pay_later' => 'Pay Later',
    ];

    public function mount()
    {
        $first = PaymentGateway::orderBy('display_order', 'asc')->first();
        if ($first) {
            $this->selectedGatewayId = $first->id;
        }
    }

    public function toggleMethod($method)
    {
        $gateway = PaymentGateway::findOrFail($this->selectedGatewayId);
        
        $mapping = PaymentGatewayMethod::where('gateway_id', $gateway->id)
            ->where('method', $method)
            ->first();

        if ($mapping) {
            $old = ['is_enabled' => $mapping->is_enabled];
            $mapping->is_enabled = !$mapping->is_enabled;
            $mapping->save();
        } else {
            $old = ['is_enabled' => false];
            $mapping = PaymentGatewayMethod::create([
                'gateway_id' => $gateway->id,
                'method' => $method,
                'is_enabled' => true,
            ]);
        }

        app(PaymentSettingsService::class)->auditChange(
            action: 'toggle_method_availability',
            entityType: PaymentGatewayMethod::class,
            entityId: $mapping->id,
            oldValue: $old,
            newValue: ['is_enabled' => $mapping->is_enabled, 'gateway' => $gateway->code, 'method' => $method]
        );

        session()->flash('success', 'Method availability updated.');
    }

    public function render()
    {
        $gateways = PaymentGateway::orderBy('display_order', 'asc')->get();
        $selectedGateway = $this->selectedGatewayId ? PaymentGateway::find($this->selectedGatewayId) : null;
        
        return view('livewire.admin.payments.gateway-methods', [
            'gateways' => $gateways,
            'selectedGateway' => $selectedGateway,
        ])->layout('layouts.admin');
    }
}
