<?php

namespace App\Livewire\Vault;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Services\Vault\VaultAccessResolver;
use App\Services\Membership\MembershipTierResolver;
use App\Services\Membership\ApplicationWizardService;

#[Layout('layouts.web-app')]
class Index extends Component
{
    public string $vaultViewMode = 'grid';
    
    // Premium Modal State
    public bool $showAccessModal = false;
    public ?array $modalData = null;

    public function setVaultView(string $mode)
    {
        $this->vaultViewMode = in_array($mode, ['grid', 'list']) ? $mode : 'grid';
    }

    public function mount(VaultAccessResolver $resolver, MembershipTierResolver $tierResolver)
    {
        $user = auth('web')->user();

        if (!$user) {
            $this->redirectRoute('login', navigate: false);
            return;
        }

        $tier = $tierResolver->resolveForUser($user);

        // Calculate Vault Access using the standard resolver
        $access = $resolver->resolveVaultAccess($user, $tier);

        // Trigger upgrade prompt if access is denied via the exact same modal system
        if (!$user->has_vault_access) {
            $this->triggerAccessModal($access, $tierResolver);
        }
    }

    protected function triggerAccessModal(array $access, MembershipTierResolver $tierResolver)
    {
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

        $this->modalData = [
            'tier_id' => $targetTierModel->id,
            'tier_name' => $targetTierModel->name,
            'price_formatted' => $targetTierModel->price > 0 ? 'INR ' . number_format($targetTierModel->price) : 'Free',
            'duration_label' => 'Year',
            'icon' => \App\Support\Archive\AccessIconNormalizer::normalize($access['reason'] ?? 'vault_access_required', $access['view_mode'] ?? 'blocked'),
            'privileges' => $targetTierModel->privileges->toArray(),
            'features' => $targetTierModel->features->toArray(),
            'product_title' => 'The Vault',
        ];

        $this->showAccessModal = true;
    }

    public function closeAccessModal(): void
    {
        $this->showAccessModal = false;
        $this->modalData = null;

        if (!auth('web')->user()?->has_vault_access) {
            $prev = url()->previous();
            $redirect = ($prev && $prev !== url('/vault')) ? $prev : '/home';
            $this->redirect($redirect, navigate: false);
        }
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

    public function render(VaultAccessResolver $resolver, MembershipTierResolver $tierResolver)
    {
        $user = auth('web')->user();
        
        if (!$user || !$user->has_vault_access) {
            // Render the locked/empty state underlying the modal
             return view('livewire.vault.index', [
                'vaultTierLabel' => 'RESTRICTED',
                'vaultAccessLabel' => 'ACCESS DENIED',
                'vaultVerificationLabel' => 'Membership Upgrade Required',
                'vaultIntroText' => 'Your digital stronghold for authenticated assets and secured certificates of provenance.',
                'vaultProtocolVersion' => 'V4.2',
                'vaultSecurityItems' => [],
                'insuredValueLabel' => null,
                'policyStatusLabel' => null,
                'vaultArtifactCount' => 0,
                'vaultArtifacts' => [],
                'supportsVaultViewToggle' => false,
             ])->layout('layouts.web-app', ['title' => 'The Vault', 'activeNav' => 'archive']);
        }

        $tier = $tierResolver->resolveForUser($user);

        // Fetch user's secured items
        $itemsQuery = call_user_func([$user, 'vaultItems']);
        
        // Scope logic
        if (method_exists($itemsQuery->getModel(), 'scopeLocked')) {
            $itemsQuery->locked();
        } else {
            $itemsQuery->where('status', 'locked');
        }

        $vaultArtifacts = $itemsQuery->orderBy('locked_at', 'desc')->get();
        
        $mappedArtifacts = $vaultArtifacts->map(function($item) {
            $img = 'https://placehold.co/800x600/17130b/d4af37?text=Secured+Asset';
            if (method_exists($item, 'getFirstMediaUrl')) {
                $img = $item->getFirstMediaUrl('default', 'thumb') ?: ($item->item_image_url ?? $img);
            } else {
                $img = $item->item_image_url ?? $img;
            }

            return (object) [
                'id' => $item->id,
                'title' => $item->item_title ?? 'Secured Asset',
                'description' => $item->item_description ?? '',
                'image_url' => $img,
                'status_badge_label' => strtoupper($item->status ?? 'LOCKED'),
                'certificate_url' => null, 
                'details_url' => null,
                'reference_label' => $item->item_ref ?? null,
            ];
        });

        $vaultSecurityItems = [
            [
                'icon' => 'mdi mdi-shield-lock-outline',
                'title' => 'End-to-End Encryption',
                'description' => 'Military-grade encryption securing your digital certificates.'
            ],
            [
                'icon' => 'mdi mdi-fingerprint',
                'title' => 'Immutable Provenance',
                'description' => 'Cryptographically verified ownership records.'
            ],
            [
                'icon' => 'mdi mdi-bank-outline',
                'title' => 'Physical Vaulting',
                'description' => 'Corresponding physical assets stored in secure climate-controlled facilities.'
            ]
        ];

        return view('livewire.vault.index', [
            'vaultTierLabel' => $tier ? $tier->name : 'No Tier',
            'vaultAccessLabel' => 'VAULT ACCESS: GRANTED',
            'vaultVerificationLabel' => 'Fully Encrypted & Authenticated',
            'vaultIntroText' => 'Your digital stronghold for authenticated assets and secured certificates of provenance.',
            'vaultProtocolVersion' => 'V4.2',
            'vaultSecurityItems' => $vaultSecurityItems,
            'insuredValueLabel' => null, 
            'policyStatusLabel' => null,
            'vaultArtifactCount' => $vaultArtifacts->count(),
            'vaultArtifacts' => $mappedArtifacts,
            'supportsVaultViewToggle' => true,
        ])->layout('layouts.web-app', [
            'title' => 'The Vault',
            'activeNav' => 'archive'
        ]);
    }
}
