<?php

namespace App\Livewire\Membership\Application;

use App\Services\Membership\ApplicationWizardService;
use App\Services\Membership\MembershipService;
use App\Services\Payments\PaymentManager;
use App\Support\Payments\PaymentPurpose;
use App\Models\MembershipApplication;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.user.blank')]
class Step7Payment extends Component
{
    public string $method = '';
    public string $tierName = '';
    public float $amount = 0.0;
    public string $amountFormatted = '';
    public string $amountFormattedPlain = '';
    public ?string $errorMessage = null;

    public function mount(ApplicationWizardService $wiz, \App\Services\Membership\MembershipService $membershipSvc): void
    {
        $this->method = config('payments.default_gateway', 'razorpay');
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

        if ((float)$tier->price <= 0.0) {
            $draft->update(['payment_status' => 'not_required']);
            $membershipSvc->submitApplication($draft);
            $this->redirect(route('membership.application.step8'));
            return;
        }

        $this->tierName = $tier->name;
        $this->amount = (float)$tier->price;
        $this->amountFormatted = 'INR ' . number_format($this->amount);
        $this->amountFormattedPlain = 'INR ' . number_format($this->amount);
    }

    public function submit(PaymentManager $paymentManager, ApplicationWizardService $wiz)
    {
        $this->errorMessage = null;

        try {
            $availabilityService = app(\App\Services\Payments\PaymentGatewayAvailabilityService::class);
            $gatewayName = $availabilityService->validateGateway($this->method);

            $draft = $wiz->getDraft();
            if (!$draft) {
                throw new \Exception('No membership application found.');
            }

            // Initiate payment via PaymentManager
            $paymentInitiation = $paymentManager->initiatePayment(
                payable: $draft,
                amount: $this->amount,
                purpose: PaymentPurpose::MEMBERSHIP_RENEWAL,
                user: Auth::user(),
                gateway: $gatewayName
            );

            $payment = $paymentInitiation['payment'];

            // Redirect user to the generic pay route
            return redirect()->route('payments.pay', $payment->id);
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.membership.application.step7-payment');
    }
}

