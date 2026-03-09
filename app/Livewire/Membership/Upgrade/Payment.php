<?php

namespace App\Livewire\Membership\Upgrade;

use App\Services\Membership\ApplicationWizardService;
use App\Services\Membership\MembershipUpgradeService;
use App\Domain\Membership\PaymentService;
use App\Models\MembershipApplication;
use App\Validation\Membership\MembershipRules;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.user.blank')]
class Payment extends Component
{
    public string $method = 'card';
    public string $card_number = '';
    public string $expiry = '';
    public string $cvv = '';
    public string $cardholder_name = '';
    public bool $save_card = false;

    public string $tierName = '';
    public int $tierId = 0;
    public float $amount = 0.0;
    public string $amountFormatted = '';
    public string $amountFormattedPlain = '';
    public ?string $errorMessage = null;

    public function mount(ApplicationWizardService $wiz): void
    {
        $draft = $wiz->getDraft();
        
        // We evaluate the draft solely as the secure transport payload for the target tier
        if (!$draft || !($draft instanceof MembershipApplication)) {
            $this->redirect('/');
            return;
        }

        $tier = $draft->membershipTier;
        if (!$tier) {
            $this->redirect('/');
            return;
        }

        $this->tierId = $tier->id;
        $this->tierName = $tier->name;
        $this->amount = (float)$tier->price;
        $this->amountFormatted = 'INR ' . number_format($this->amount);
        $this->amountFormattedPlain = 'INR ' . number_format($this->amount);
    }

    public function submit(PaymentService $paymentSvc, MembershipUpgradeService $upgradeSvc, ApplicationWizardService $wiz)
    {
        $this->errorMessage = null;

        $rules = [
            'method' => 'required|in:card,wallet',
        ];

        if ($this->method === 'card') {
            $rules['card_number'] = 'required|string|min:12|max:19';
            $rules['expiry'] = 'required|string|regex:/^\d{2}\/\d{2}$/';
            $rules['cvv'] = 'required|string|min:3|max:4';
            $rules['cardholder_name'] = 'required|string|min:2|max:80';
        }

        $this->validate($rules);

        try {
            $draft = $wiz->getDraft();
            
            // Re-evaluating the payment logic using the draft context strictly
            $paymentData = [
                'amount' => $this->amount,
                'method' => $this->method,
                'cardholder_name' => $this->cardholder_name,
                'last4' => substr(str_replace(' ', '', $this->card_number), -4),
                'brand' => 'Visa', 
                'currency' => 'INR'
            ];

            // 1. Process Payment to the Draft precisely to bypass Application logic
            $paymentSvc->processTestPayment($draft, $paymentData);

            // 2. Perform the actual Membership Upgrade via the dedicated service cleanly
            $upgradeSvc->upgradeUserMembership(Auth::user(), $this->tierId);

            // Navigate to upgrade completed confirmation cleanly
            return redirect()->route('membership.upgrade.success');

        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.membership.upgrade.payment');
    }
}
