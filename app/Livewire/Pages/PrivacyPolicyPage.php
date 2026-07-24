<?php

namespace App\Livewire\Pages;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.web-app')]
class PrivacyPolicyPage extends Component
{
    public function render()
    {
        $contactConfig = \App\Models\ContactConfig::first();
        return view('livewire.pages.privacy-policy-page', [
            'contactConfig' => $contactConfig
        ]);
    }
}
