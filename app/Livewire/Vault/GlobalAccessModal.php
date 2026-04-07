<?php

namespace App\Livewire\Vault;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Services\Vault\VaultAccessResolver;
use App\Services\Membership\MembershipTierResolver;
use App\Services\Membership\MembershipUpgradeService;
use App\Services\Membership\ApplicationWizardService;

class GlobalAccessModal extends Component
{
    public bool $showAccessModal = false;
    public ?array $modalData = null;

    #[On('open-vault-modal')]
    public function openModal(VaultAccessResolver $resolver, MembershipTierResolver $tierResolver, MembershipUpgradeService $upgradeSvc)
    {
        $user = auth('web')->user();

        if (!$user) {
            $this->redirectRoute('login', navigate: false);
            return;
        }

        $tier = $tierResolver->resolveForUser($user);
        $access = $resolver->resolveVaultAccess($user, $tier);

        // If they have access somehow, just go to vault
        if ($user->has_vault_access) {
            $this->redirectRoute('vault.index', navigate: false);
            return;
        }

        // Build the modal logic
        $targetTierId = null;
        if (!empty($access['actions'])) {
            foreach ($access['actions'] as $action) {
                if ($action['type'] === 'upgrade_membership' && !empty($action['target_tier']['id'])) {
                    $targetTierId = $action['target_tier']['id'];
                    break;
                }
            }
        }

        if (!$targetTierId) {
            $this->redirect('/membership/apply-intro');
            return;
        }

        $targetTierModel = $tierResolver->getTierWithDetails($targetTierId);
        
        if (!$targetTierModel) {
            $this->redirect('/membership/apply-intro');
            return;
        }

        // Fetch prorated quote so the modal can show the real payable amount
        $quote = $upgradeSvc->getUpgradeQuote($user, $targetTierId);
        $unusedCredit  = $quote['unused_credit'] ?? 0.0;
        $payableAmount = $quote['payable_amount'] ?? (float)$targetTierModel->price;
        $isProrated    = $unusedCredit > 0;

        $this->modalData = [
            'tier_id'           => $targetTierModel->id,
            'tier_name'         => $targetTierModel->name,
            'price_formatted'   => $targetTierModel->price > 0 ? 'INR ' . number_format($targetTierModel->price) : 'Free',
            'duration_label'    => 'Year',
            'icon'              => \App\Support\Archive\AccessIconNormalizer::normalize($access['reason'] ?? 'vault_access_required', $access['view_mode'] ?? 'blocked'),
            'privileges'        => $targetTierModel->privileges->toArray(),
            'features'          => $targetTierModel->features->toArray(),
            'product_title'     => 'The Vault',
            // Prorated quote fields
            'is_prorated'       => $isProrated,
            'unused_credit'     => $unusedCredit,
            'payable_amount'    => $payableAmount,
            'payable_formatted' => 'INR ' . number_format($payableAmount, 2),
            'credit_formatted'  => 'INR ' . number_format($unusedCredit, 2),
        ];

        $this->showAccessModal = true;
    }

    public function closeAccessModal(): void
    {
        $this->showAccessModal = false;
        $this->modalData = null;
    }

    public function proceedToSubscribe(ApplicationWizardService $wiz)
    {
        if (!auth('web')->check()) {
            return redirect('/membership/apply-intro');
        }

        if (!$this->modalData || empty($this->modalData['tier_id'])) {
            return redirect(route('membership.application.step1'));
        }

        $draft = $wiz->getOrCreateDraft();
        
        if ($draft instanceof \App\Models\MembershipApplication) {
            $draft->update([
                'selected_tier_id' => $this->modalData['tier_id']
            ]);
        }

        return redirect()->route('membership.upgrade.payment');
    }

    public function render()
    {
        return view('livewire.vault.global-access-modal');
    }
}
