<?php

namespace App\Livewire\Membership\Application;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.user.blank')]
class Step8Success extends Component
{
    public function render()
    {
        return view('livewire.membership.application.step8-success');
    }
}
