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

    public ?array $selectedArtifact = null;
    public $removalMessage = '';
    public bool $showRemovalModal = false;

    public function selectArtifact(int $id)
    {
        $user = auth('web')->user();
        $item = $user->vaultItems()->find($id);
        
        if (!$item) return;

        $img = 'https://placehold.co/800x600/17130b/d4af37?text=Secured+Asset';
        $img = $item->display_image_url ?? $img;

        $this->selectedArtifact = [
            'id' => $item->id,
            'title' => $item->item_title ?? 'Secured Asset',
            'description' => $item->notes ?? '',
            'image_url' => $img,
            'status_badge_label' => strtoupper($item->status ?? 'LOCKED'),
            'reference_label' => $item->item_ref ?? null,
            'quantity' => $item->quantity ?? 1,
            'unit_price' => $item->unit_price ?? $item->price,
            'total_value' => $item->total_value,
            'currency' => $item->currency ?? 'INR',
            'locked_at_human' => $item->locked_at ? $item->locked_at->format('d M Y') : 'N/A',
            'has_pending_request' => $item->pendingRemovalRequest()->exists(),
        ];
    }

    public function closeArtifactModal()
    {
        $this->selectedArtifact = null;
        $this->removalMessage = '';
        $this->showRemovalModal = false;
    }

    public function openRemovalModal()
    {
        if (!$this->selectedArtifact) return;
        $this->showRemovalModal = true;
    }

    public function submitRemovalRequest(\App\Services\VaultService $service)
    {
        if (!$this->selectedArtifact) return;

        $user = auth('web')->user();
        $item = $user->vaultItems()->find($this->selectedArtifact['id']);

        if (!$item) return;

        try {
            $service->requestRemoval($item, $user, $this->removalMessage);
            session()->flash('success', 'Removal request submitted successfully. Our team will review it shortly.');
            $this->closeArtifactModal();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
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

    public function render(VaultAccessResolver $resolver, MembershipTierResolver $tierResolver, \App\Services\VaultService $vaultService)
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
                'vaultSummary' => [
                    'total_items_count' => 0,
                    'total_value' => 0,
                    'pending_requests_count' => 0
                ],
                'mappedArtifacts' => [],
                'vaultArtifactCount' => 0,
                'supportsVaultViewToggle' => false,
             ])->layout('layouts.web-app', ['title' => 'The Vault', 'activeNav' => 'archive']);
        }

        $tier = $tierResolver->resolveForUser($user);
        $vaultSummary = $vaultService->getVaultSummary($user);

        // Fetch user's secured items
        $vaultArtifacts = $user->vaultItems()
            ->with('pendingRemovalRequest')
            ->locked()
            ->orderBy('locked_at', 'desc')
            ->get();
        
        $mappedArtifacts = $vaultArtifacts->map(function($item) {
            $img = 'https://placehold.co/800x600/17130b/d4af37?text=Secured+Asset';
            $img = $item->display_image_url ?? $img;

            return (object) [
                'id' => $item->id,
                'title' => $item->item_title ?? 'Secured Asset',
                'description' => $item->notes ?? '',
                'image_url' => $img,
                'status_badge_label' => strtoupper($item->status ?? 'LOCKED'),
                'reference_label' => $item->item_ref ?? null,
                'quantity' => $item->quantity ?? 1,
                'unit_price' => $item->unit_price ?? $item->price,
                'total_value' => $item->total_value,
                'currency' => $item->currency ?? 'INR',
                'locked_at_human' => $item->locked_at ? $item->locked_at->format('d M Y') : 'N/A',
                'has_pending_request' => (bool) $item->pendingRemovalRequest,
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
            'mappedArtifacts' => $mappedArtifacts,
            'vaultSummary' => $vaultSummary,
            'supportsVaultViewToggle' => true,
        ])->layout('layouts.web-app', [
            'title' => 'The Vault',
            'activeNav' => 'archive'
        ]);
    }
}
