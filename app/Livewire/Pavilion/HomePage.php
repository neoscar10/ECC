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

    public function mount(CmsBlockWebService $cmsService)
    {
        $user = Auth::user();
        
        // Fetch resolved blocks for both zones
        $this->homeHeroBlocks = $cmsService->resolveBlocksForPlacement('home-hero', $user)->toArray();
        $this->exploreBlocks = $cmsService->resolveBlocksForPlacement('explore', $user)->toArray();
    }

    public function render()
    {
        return view('livewire.pavilion.home-page', [
            'title' => 'Home',
            'activeNav' => 'explore', // Keep active nav as explore to match bottom nav
        ]);
    }
}
