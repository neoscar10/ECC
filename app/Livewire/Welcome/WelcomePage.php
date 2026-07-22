<?php

namespace App\Livewire\Welcome;

use Livewire\Component;
use Illuminate\Support\Facades\Route;

class WelcomePage extends Component
{
    public string $enterUrl;

    public function mount(): void
    {
        // If already logged in, "Enter the Club" goes home
        if (auth()->check()) {
            $this->enterUrl = Route::has('home') ? route('home') : url('/home');
            return;
        }

        // Guest: go to gated entry page (not login)
        if (\Illuminate\Support\Facades\Route::has('gated.entry')) {
            $this->enterUrl = route('gated.entry');
            return;
        }
        $this->enterUrl = url('/gated-entry');
    }

    public function render()
    {
        return view('livewire.welcome.welcome-page')
            ->layout('layouts.user.blank', [
                'title' => 'Executive Club Cricket',
            ]);
    }
}
