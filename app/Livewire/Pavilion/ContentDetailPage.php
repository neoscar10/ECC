<?php

namespace App\Livewire\Pavilion;

use App\Services\Pavilion\PavilionContentService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.user.app')]
class ContentDetailPage extends Component
{
    public array $vm = [];

    public function mount(PavilionContentService $service, string $type, string $slugOrId)
    {
        // For CMS blocks, slugOrId is currently the numeric ID
        $this->vm = $service->getDetailViewModel(Auth::user(), $type, $slugOrId);
    }

    public function render()
    {
        return view('livewire.pavilion.content-detail-page', [
            'title' => $this->vm['title'] ?? 'The Pavilion',
            'activeNav' => 'explore',
        ]);
    }
}
