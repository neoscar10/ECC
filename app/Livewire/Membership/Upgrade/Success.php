<?php

namespace App\Livewire\Membership\Upgrade;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.user.blank')]
class Success extends Component
{
    public function render()
    {
        return view('livewire.membership.upgrade.success');
    }
}
