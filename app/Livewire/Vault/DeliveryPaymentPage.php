<?php

namespace App\Livewire\Vault;

use App\Models\VaultRemovalRequest;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.user.blank')]
class DeliveryPaymentPage extends Component
{
    public int $requestId;
    
    public string $selectedPaymentMethod = 'card_mock_1';
    public array $savedPaymentMethods = [];
    public array $walletOptions = [];

    public float $amount = 0.0;
    public string $amountFormatted = '';
    public string $currency = 'INR';
    
    public string $itemTitle = '';
    public ?string $itemRef = null;
    public string $courierName = '';
    
    public ?string $errorMessage = null;

    public function mount(int $requestId): void
    {
        $user = Auth::user();
        
        $request = VaultRemovalRequest::with('vaultItem')->findOrFail($requestId);

        if ($request->user_id !== $user->id) {
            abort(403, 'Unauthorized access to delivery request.');
        }

        if (!in_array($request->payment_status, ['pending_payment', 'payment_failed'])) {
            // Already paid or not awaiting payment
            $this->redirect(route('vault.index'));
            return;
        }

        $this->requestId = $request->id;
        $this->amount = (float) $request->delivery_fee;
        $this->currency = $request->delivery_currency ?? 'INR';
        $this->amountFormatted = $this->currency . ' ' . number_format($this->amount, 2);
        
        $this->itemTitle = $request->vaultItem->item_title ?? 'Secured Asset';
        $this->itemRef = $request->vaultItem->item_ref;
        $this->courierName = $request->selected_courier_name ?? 'Standard Delivery';

        // Mock Payment Methods
        $this->savedPaymentMethods = [
            (object) [
                'id' => 'card_mock_1',
                'brand_label' => 'VISA',
                'display_name' => '•••• 4242',
                'expiry_label' => '12/26',
                'is_default' => true,
            ],
            (object) [
                'id' => 'card_mock_2',
                'brand_label' => 'MC',
                'display_name' => '•••• 5555',
                'expiry_label' => '08/25',
                'is_default' => false,
            ],
        ];

        $this->walletOptions = [
            ['label' => 'Apple Pay', 'value' => 'apple_pay', 'icon' => 'mdi mdi-apple'],
            ['label' => 'Google Pay', 'value' => 'google_pay', 'icon' => 'mdi mdi-google'],
        ];
    }

    public function handleAddPaymentMethod()
    {
        session()->flash('info', 'Secure payment gateway integration is currently in preview mode.');
    }

    public function submit()
    {
        $this->errorMessage = null;

        if (!$this->selectedPaymentMethod) {
            $this->errorMessage = 'Please select a payment method.';
            return;
        }

        try {
            $user = Auth::user();
            $request = VaultRemovalRequest::findOrFail($this->requestId);

            if ($request->user_id !== $user->id) {
                throw new \Exception('Unauthorized access.');
            }

            if (!in_array($request->payment_status, ['pending_payment', 'payment_failed'])) {
                throw new \Exception('This request is no longer awaiting payment.');
            }

            // Simulate successful payment
            $request->update([
                'payment_status' => VaultRemovalRequest::PAYMENT_PAID,
                'paid_at' => now(),
                'payment_reference' => 'PAY-ECC-' . strtoupper(uniqid()),
            ]);

            session()->flash('success', 'Delivery fee paid successfully. Your request is now pending admin review.');
            return redirect()->route('vault.index');

        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.vault.payment');
    }
}
