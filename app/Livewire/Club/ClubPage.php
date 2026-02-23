<?php

namespace App\Livewire\Club;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Services\Club\ClubPageService;

#[Layout('layouts.user.app')]
class ClubPage extends Component
{
    public array $vm = [];

    public function mount(ClubPageService $service): void
    {
        $this->vm = $service->getViewModel(auth()->user());
    }

    public function render()
    {
        return view('livewire.club.club-page', [
            'title' => 'The Club',
            'activeNav' => 'club',
        ]);
    }
}
