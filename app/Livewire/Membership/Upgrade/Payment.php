<?php

namespace App\Livewire\Membership\Upgrade;

use App\Services\Membership\ApplicationWizardService;
use App\Services\Membership\MembershipUpgradeService;
use App\Services\Payments\PaymentManager;
use App\Support\Payments\PaymentPurpose;
use App\Models\MembershipApplication;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.user.blank')]
class Payment extends Component
{
    public string $method = '';
    public string $tierName = '';
    public int $tierId = 0;
    public float $amount = 0.0;
    public string $amountFormatted = '';
    public string $amountFormattedPlain = '';
    public ?array $quoteData = null;
    public ?string $errorMessage = null;

    public function mount(ApplicationWizardService $wiz, MembershipUpgradeService $upgradeSvc): void
    {
        $this->method = config('payments.default_gateway', 'razorpay');
        $draft = $wiz->getDraft();
        
        // We evaluate the draft solely as the secure transport payload for the target tier
        if (!$draft || !($draft instanceof MembershipApplication)) {
            $this->redirect('/');
            return;
        }

        // Guard against reusing a draft that was already consumed by a prior upgrade
        if ($draft->status === 'upgrade_completed') {
            $this->redirect(route('home'));
            return;
        }

        $tier = $draft->membershipTier;
        if (!$tier) {
            $this->redirect('/');
            return;
        }

        $this->tierId = $tier->id;
        $this->tierName = $tier->name;
        
        $quote = $upgradeSvc->getUpgradeQuote(Auth::user(), $this->tierId);
        
        $this->amount = $quote['payable_amount'];
        // Use 2 decimal places for consistent display — avoids cosmetic rounding vs charged amount
        $this->amountFormatted = 'INR ' . number_format($this->amount, 2);
        $this->amountFormattedPlain = 'INR ' . number_format($this->amount, 2);
        // Additional UI breakdown properties
        $this->quoteData = $quote;
    }

    public function submit(PaymentManager $paymentManager, MembershipUpgradeService $upgradeSvc, ApplicationWizardService $wiz)
    {
        $this->errorMessage = null;

        try {
            $availabilityService = app(\App\Services\Payments\PaymentGatewayAvailabilityService::class);
            $gatewayName = $availabilityService->validateGateway($this->method);

            $user = Auth::user();
            $draft = $wiz->getDraft();
            if (!$draft) {
                throw new \Exception('No upgrade draft found.');
            }

            // Re-validate quote server-side at submit time (prevents stale amounts from UI)
            $quote = $upgradeSvc->getUpgradeQuote($user, $this->tierId);

            if (!$quote['is_eligible']) {
                $this->errorMessage = $quote['reason'] ?? 'This upgrade is no longer available.';
                return;
            }

            // Initiate payment via PaymentManager
            $paymentInitiation = $paymentManager->initiatePayment(
                payable: $draft,
                amount: $quote['payable_amount'],
                purpose: PaymentPurpose::MEMBERSHIP_UPGRADE,
                user: $user,
                gateway: $gatewayName,
                context: [
                    'meta' => [
                        'upgrade_context' => $quote
                    ]
                ]
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
        return view('livewire.membership.upgrade.payment');
    }
}

