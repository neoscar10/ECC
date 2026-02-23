<?php

namespace App\Livewire\Membership\Application;

use App\Services\Membership\ApplicationWizardService;
use App\Services\Membership\MembershipService;
use App\Domain\Membership\PaymentService;
use App\Validation\Membership\MembershipRules;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.user.blank')]
class Step7Payment extends Component
{
    public string $method = 'card';
    public string $card_number = '';
    public string $expiry = '';
    public string $cvv = '';
    public string $cardholder_name = '';
    public bool $save_card = false;

    public string $tierName = '';
    public float $amount = 0.0;
    public string $amountFormatted = '';
    public string $amountFormattedPlain = '';
    public ?string $errorMessage = null;

    public function mount(ApplicationWizardService $wiz): void
    {
        $draft = $wiz->getDraft();
        
        if (!$draft || !($draft instanceof MembershipApplication)) {
            $this->redirect(route('membership.application.step1'));
            return;
        }

        $tier = $draft->membershipTier;
        if (!$tier) {
            $this->redirect(route('membership.application.step6'));
            return;
        }

        $this->tierName = $tier->name;
        $this->amount = (float)$tier->price;
        $this->amountFormatted = 'INR ' . number_format($this->amount);
        $this->amountFormattedPlain = 'INR ' . number_format($this->amount);
    }

    public function submit(PaymentService $paymentSvc, MembershipService $membershipSvc, ApplicationWizardService $wiz)
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
            
            // Prepare data for service (excluding raw card data as per service security rule)
            $paymentData = [
                'amount' => $this->amount,
                'method' => $this->method,
                'cardholder_name' => $this->cardholder_name,
                'last4' => substr(str_replace(' ', '', $this->card_number), -4),
                'brand' => 'Visa', // Dummy
                'currency' => 'INR'
            ];

            // 1. Process Payment
            $paymentSvc->processTestPayment($draft, $paymentData);

            // 2. Submit Application (per API pattern)
            $membershipSvc->submitApplication($draft);

            return redirect()->route('membership.application.step8');
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.membership.application.step7-payment');
    }
}
