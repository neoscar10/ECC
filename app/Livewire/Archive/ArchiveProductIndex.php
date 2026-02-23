<?php

namespace App\Livewire\Archive;

use App\Services\Archive\ArchiveProductService;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.user.app')]
class ArchiveProductIndex extends Component
{
    use WithPagination;

    public $categoryId = null;
    
    protected $archiveService;

    public function boot(ArchiveProductService $archiveService)
    {
        $this->archiveService = $archiveService;
    }

    public function updatingCategoryId()
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();
        $userTier = $user ? $user->currentMembership?->membershipTier : null;

        $products = $this->archiveService->getProducts(
            $user,
            $userTier,
            ['category_id' => $this->categoryId],
            12
        );

        return view('livewire.archive.archive-product-index', [
            'products' => $products,
            'activeNav' => 'archive',
            'title' => 'The Archive',
        ]);
    }
}
