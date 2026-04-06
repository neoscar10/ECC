<?php

namespace App\Livewire\Membership\Upgrade;

use App\Services\Membership\ApplicationWizardService;
use App\Services\Membership\MembershipUpgradeService;
use App\Domain\Membership\PaymentService;
use App\Models\MembershipApplication;
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
    public ?array $quoteData = null;
    public ?string $errorMessage = null;

    public function mount(ApplicationWizardService $wiz, MembershipUpgradeService $upgradeSvc): void
    {
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
            $user = Auth::user();
            $draft = $wiz->getDraft();

            // Re-validate quote server-side at submit time (prevents stale amounts from UI)
            $quote = $upgradeSvc->getUpgradeQuote($user, $this->tierId);

            if (!$quote['is_eligible']) {
                $this->errorMessage = $quote['reason'] ?? 'This upgrade is no longer available.';
                return;
            }

            // Build payment data including proration audit context
            $paymentData = [
                'amount'          => $quote['payable_amount'],
                'method'          => $this->method,
                'cardholder_name' => $this->cardholder_name,
                'last4'           => substr(str_replace(' ', '', $this->card_number), -4),
                'brand'           => 'Visa', 
                'currency'        => 'INR',
                // Proration audit trail — persisted in payment meta_json
                'upgrade_context' => [
                    'current_membership_id' => $quote['current_membership_id'],
                    'current_tier_id'       => $quote['current_tier']['id'] ?? null,
                    'current_tier_name'     => $quote['current_tier']['name'] ?? null,
                    'current_tier_price'    => $quote['current_tier_price'],
                    'target_tier_id'        => $quote['target_tier']['id'],
                    'target_tier_name'      => $quote['target_tier']['name'],
                    'target_tier_price'     => $quote['target_tier_price'],
                    'total_duration_days'   => $quote['total_duration_days'],
                    'remaining_days'        => $quote['remaining_days'],
                    'unused_credit'         => $quote['unused_credit'],
                    'payable_amount'        => $quote['payable_amount'],
                    'currency'              => $quote['currency'],
                    'is_prorated'           => ($quote['unused_credit'] > 0),
                    'calculated_at'         => now()->toIso8601String(),
                    'source'                => 'web_upgrade_flow',
                ],
            ];

            // 1. Process Payment against the draft record
            $paymentSvc->processTestPayment($draft, $paymentData);

            // 2. Perform the actual Membership Upgrade via the dedicated service
            $upgradeSvc->upgradeUserMembership($user, $this->tierId, $this->quoteData);

            // 3. Mark the draft as consumed so it cannot be reused
            $upgradeSvc->consumeUpgradeDraft($draft);

            // Navigate to upgrade completed confirmation
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
