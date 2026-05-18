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
    
    public string $method = 'card';
    public string $card_number = '';
    public string $expiry = '';
    public string $cvv = '';
    public string $cardholder_name = '';
    public bool $save_card = false;

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
    }

    public function submit()
    {
        $this->errorMessage = null;

        $rules = [
            'method' => 'required|in:card,upi',
        ];

        if ($this->method === 'card') {
            $rules['card_number'] = 'required|string|min:12|max:19';
            $rules['expiry'] = 'required|string|regex:/^\d{2}\/\d{2}$/';
            $rules['cvv'] = 'required|string|min:3|max:4';
            $rules['cardholder_name'] = 'required|string|min:2|max:80';
        }

        $this->validate($rules);

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
