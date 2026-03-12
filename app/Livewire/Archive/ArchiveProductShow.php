<?php

namespace App\Livewire\Archive;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Services\Archive\ArchiveProductService;
use App\Services\Membership\MembershipTierResolver;

class ArchiveProductShow extends Component
{
    public int $productId;
    public array $detail = [];
    public ?string $enquiryMessage = null;
    public bool $enquirySuccess = false;

    public function mount(int $id, ArchiveProductService $service, MembershipTierResolver $tierResolver)
    {
        $this->productId = $id;
        
        $user = auth('web')->user();
        $tier = $tierResolver->resolveForUser($user);
        
        $this->detail = $service->getProductDetailDto($user, $tier, $id);
    }

    public function submitEnquiry(\App\Services\Archive\ArchiveEnquiryService $service)
    {
        $this->validate([
            'enquiryMessage' => 'nullable|string|max:2000'
        ]);

        $user = auth('web')->user();

        if (!$user) {
            return;
        }

        $service->createEnquiry($user, $this->productId, $this->enquiryMessage);

        $this->enquirySuccess = true;
        $this->enquiryMessage = null;
    }

    public function render()
    {
        return view('livewire.archive.archive-product-show', [
            'activeNav' => 'archive'
        ])
        ->layout('layouts.web-app', [
            'hideBottomNav' => true,
            'title' => $this->detail['title'] ?? 'Archive Detail'
        ]);
    }
}
