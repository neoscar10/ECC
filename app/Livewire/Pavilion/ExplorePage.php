<?php

namespace App\Livewire\Pavilion;

use App\Services\Pavilion\PavilionExploreService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.user.app')]
class ExplorePage extends Component
{
    public array $vm = [];

    public function mount(PavilionExploreService $service)
    {
        $this->vm = $service->getExploreViewModel(Auth::user());
    }

    public function render()
    {
        return view('livewire.pavilion.explore-page', [
            'joinClubUrl' => $this->vm['joinClubUrl'] ?? null,
        ])->layout('layouts.user.app', [
            'title' => $this->vm['title'] ?? 'Explore',
            'activeNav' => 'explore',
        ]);
    }
}
