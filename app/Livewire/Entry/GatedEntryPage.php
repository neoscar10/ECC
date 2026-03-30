<?php

namespace App\Livewire\Entry;

use Livewire\Component;
use Illuminate\Support\Facades\Route;

class GatedEntryPage extends Component
{
    public string $applyUrl;
    public string $loginUrl;
    public string $previewUrl;

    public function mount(): void
    {
        // If already authenticated, skip this page
        if (auth()->check()) {
            $to = Route::has('home') ? route('home') : url('/home');
            redirect()->to($to)->send();
            return;
        }

        // Apply for membership (go to intro page first)
        if (Route::has('membership.apply-intro')) {
            $this->applyUrl = route('membership.apply-intro');
        } else {
            $this->applyUrl = url('/membership/apply-intro');
        }

        // Existing member login
        $this->loginUrl = \Illuminate\Support\Facades\Route::has('login')
            ? route('login')
            : url('/login');

        // Guest preview (prefer named route if exists)
        if (Route::has('guest.preview')) {
            $this->previewUrl = route('guest.preview');
        } elseif (Route::has('archive.index')) {
            $this->previewUrl = route('archive.index') . '?guest=1';
        } else {
            $this->previewUrl = url('/preview');
        }
    }

    public function render()
    {
        return view('livewire.entry.gated-entry-page')
            ->layout('layouts.user.blank', ['title' => 'Gated Entry - The Pavilion Archives']);
    }
}
