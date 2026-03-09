<?php

namespace App\Livewire\Pavilion;

use App\Services\Cms\CmsBlockWebService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.user.app')]
class HomePage extends Component
{
    public array $homeHeroBlocks = [];
    public array $exploreBlocks = [];

    public bool $showAccessModal = false;
    public ?array $modalData = null;

    public function mount(CmsBlockWebService $cmsService)
    {
        $user = Auth::user();
        
        // Fetch resolved blocks for both zones
        $this->homeHeroBlocks = $cmsService->resolveBlocksForPlacement('home-hero', $user)->toArray();
        $this->exploreBlocks = $cmsService->resolveBlocksForPlacement('explore', $user)->toArray();
    }

    public function openAccessModal(?int $targetTierId, string $title, string $icon, \App\Services\Membership\MembershipTierResolver $tierResolver)
    {
        if (!$targetTierId) {
            return redirect('/membership/apply-intro');
        }

        $targetTierModel = $tierResolver->getTierWithDetails($targetTierId);
        
        if (!$targetTierModel) {
            return redirect('/membership/apply-intro');
        }

        $this->modalData = [
            'tier_id' => $targetTierModel->id,
            'tier_name' => $targetTierModel->name,
            'price_formatted' => $targetTierModel->price > 0 ? 'INR ' . number_format($targetTierModel->price) : 'Free',
            'duration_label' => 'Year',
            'icon' => $icon,
            'privileges' => $targetTierModel->privileges->toArray(),
            'features' => $targetTierModel->features->toArray(),
            'product_title' => $title,
        ];

        $this->showAccessModal = true;
    }

    public function closeAccessModal(): void
    {
        $this->showAccessModal = false;
        $this->modalData = null;
    }

    public function proceedToSubscribe(\App\Services\Membership\ApplicationWizardService $wiz)
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
        return view('livewire.pavilion.home-page')->layout('layouts.user.app', [
            'title' => 'Home',
            'activeNav' => 'explore', // Keep active nav as explore to match bottom nav
        ]);
    }
}
