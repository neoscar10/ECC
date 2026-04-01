<?php

namespace App\Livewire\Archive;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Services\Archive\ArchiveProductService;
use App\Services\Archive\ArchiveAccessService;
use App\Services\Membership\MembershipTierResolver;
use App\Models\Archive\ArchiveCategory;
use App\Models\MembershipTier;
use App\Models\Archive\ArchiveProduct;
use App\Services\Archive\ArchiveAccessResolver;
use App\Services\Membership\ApplicationWizardService;

#[Layout('layouts.web-app')]
class ArchiveBrowse extends Component
{
    use WithPagination;

    public string $activeTab = 'all';
    public string $q = '';
    public bool $showFilters = false;

    // Premium Modal State
    public bool $showAccessModal = false;
    public ?array $modalData = null;

    public function setTab(string $key): void
    {
        $this->activeTab = $key;
        $this->resetPage();
    }

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function toggleFilters(): void
    {
        $this->showFilters = !$this->showFilters;
    }

    public function openAccessModal(int $productId, ArchiveAccessResolver $resolver, MembershipTierResolver $tierResolver)
    {
        $user = auth('web')->user();
        $tier = $tierResolver->resolveForUser($user);
        
        $product = ArchiveProduct::with(['restrictedMinTier', 'restrictedPrivateTier', 'clearViewTiers', 'visibilityTiers'])->find($productId);
        if (!$product) return;

        $access = $resolver->resolveProductAccess($product, $user, $tier);

        // Find the target tier from the access actions
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
            // Fallback if no specific tier is recommended, just take them to intro
            return redirect('/membership/apply-intro');
        }

        // Fetch the full tier data via shared API service
        $targetTierModel = $tierResolver->getTierWithDetails($targetTierId);
        
        if (!$targetTierModel) {
            return redirect('/membership/apply-intro');
        }

        $this->modalData = [
            'tier_id' => $targetTierModel->id,
            'tier_name' => $targetTierModel->name,
            'price_formatted' => $targetTierModel->price > 0 ? 'INR ' . number_format($targetTierModel->price) : 'Free',
            'duration_label' => 'Year',
            'icon' => \App\Support\Archive\AccessIconNormalizer::normalize($access['reason'] ?? null, $access['view_mode'] ?? 'blocked'),
            'privileges' => $targetTierModel->privileges->toArray(),
            'features' => $targetTierModel->features->toArray(),
            'product_title' => $product->title,
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

        // Auth User - generate the safe draft application 
        $draft = $wiz->getOrCreateDraft();
        
        // Inject the selected tier so they land perfectly on confirmation
        // But the wizard strictly prevents arbitrary updates if we don't jump them nicely.
        // It's safest to draft it, and send them to Step 6 where they just "confirm" it.
        if ($draft instanceof \App\Models\MembershipApplication) {
            $draft->update([
                'selected_tier_id' => $this->modalData['tier_id']
            ]);
        }

        return redirect()->route('membership.upgrade.payment');
    }

    public function render(ArchiveProductService $service, MembershipTierResolver $tierResolver, \App\Services\Archive\ArchiveAccessService $accessService)
    {
        $user = auth('web')->user();
        $tier = $tierResolver->resolveForUser($user);
        $tierId = $tier?->id;

        // Fetch Dynamic Categories (SSOT)
        $categoryQuery = \App\Models\Archive\ArchiveCategory::query();
        $accessService->applyAccessibleScope($categoryQuery, $tierId, false); // false to exclude categories the user doesn't have access to
        $categories = $categoryQuery->orderBy('sort_order')->orderBy('title')->get();

        $tabs = $categories->map(function($cat) {
            return [
                'key' => (string) $cat->id,
                'label' => $cat->title
            ];
        })->prepend(['key' => 'all', 'label' => 'All'])->toArray();

        $filters = [
            'q' => $this->q ?: null,
            'category_id' => $this->activeTab === 'all' ? null : (int) $this->activeTab,
        ];

        $products = $service->getProducts($user, $tier, $filters);

        return view('livewire.archive.archive-browse', [
            'products' => $products,
            'tabs' => $tabs,
        ])->layout('layouts.web-app', [
            'title' => 'Archive',
            'activeNav' => 'archive'
        ]);
    }

}
