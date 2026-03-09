<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.user.app')]
class SettingsPage extends Component
{
    public function render()
    {
        return view('livewire.settings.settings-page')->layout('layouts.user.app', [
            'title' => 'Settings',
            'activeNav' => 'settings',
        ]);
    }
}
