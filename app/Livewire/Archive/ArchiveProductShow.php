<?php

namespace App\Livewire\Archive;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Services\Archive\ArchiveProductService;
use App\Services\Membership\MembershipTierResolver;

#[Layout('layouts.user.app')]
class ArchiveProductShow extends Component
{
    public int $productId;
    public array $detail = [];

    public function mount(int $id, ArchiveProductService $service, MembershipTierResolver $tierResolver)
    {
        $this->productId = $id;
        
        $user = auth('web')->user();
        $tier = $tierResolver->resolveForUser($user);
        
        $this->detail = $service->getProductDetailDto($user, $tier, $id);
    }

    public function render()
    {
        return view('livewire.archive.archive-product-show', [
            'activeNav' => 'archive'
        ])->with([
            'title' => $this->detail['title'] ?? 'Archive Detail'
        ]);
    }
}
