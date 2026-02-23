<?php

namespace App\Livewire\Membership;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Route;

#[Layout('layouts.user.blank')]
class ApplyIntroPage extends Component
{
    public string $beginUrl;
    public string $tiersUrl;

    public function mount(): void
    {
        // Begin URL must point to the FIRST registration step page.
        $this->beginUrl = route('membership.application.step1');

        $this->tiersUrl = Route::has('membership.tiers')
            ? route('membership.tiers')
            : url('/membership/tiers');
    }

    public function render()
    {
        return view('livewire.membership.apply-intro-page');
    }
}
