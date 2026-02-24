<?php

namespace App\Livewire\Archive;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Services\Archive\ArchiveProductService;
use App\Services\Archive\ArchiveAccessService;
use App\Services\Membership\MembershipTierResolver;
use App\Models\Archive\ArchiveCategory;

#[Layout('layouts.user.app')]
class ArchiveBrowse extends Component
{
    use WithPagination;

    public string $activeTab = 'all';
    public string $q = '';
    public bool $showFilters = false;

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

    public function render(ArchiveProductService $service, MembershipTierResolver $tierResolver, \App\Services\Archive\ArchiveAccessService $accessService)
    {
        $user = auth('web')->user();
        $tier = $tierResolver->resolveForUser($user);
        $tierId = $tier?->id;

        // Fetch Dynamic Categories (SSOT)
        $categoryQuery = \App\Models\Archive\ArchiveCategory::query();
        $accessService->applyAccessibleScope($categoryQuery, $tierId, true); // true to include locked but marked
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
        ])->with([
            'title' => 'The Archive',
            'activeNav' => 'archive'
        ]);
    }

}
