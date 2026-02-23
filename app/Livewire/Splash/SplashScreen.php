<?php

namespace App\Livewire\Splash;

use Livewire\Component;
use Illuminate\Support\Facades\Route;

class SplashScreen extends Component
{
    public int $delayMs = 1800;
    public string $redirectTo;
    public string $version = 'v1.0.4';

    public function mount(): void
    {
        // Optional: if you have app version in config, use it; otherwise keep default
        $cfgVersion = config('app.version');
        if (is_string($cfgVersion) && $cfgVersion !== '') {
            $this->version = $cfgVersion;
        }

        $this->redirectTo = $this->resolveRedirectTo();
    }

    private function resolveRedirectTo(): string
    {
        // Session-authenticated web user
        if (auth()->check()) {
            if (\Illuminate\Support\Facades\Route::has('home')) {
                return route('home'); // /home
            }
            return url('/home');
        }

        // Guest user -> welcome
        if (\Illuminate\Support\Facades\Route::has('welcome')) {
            return route('welcome'); // /welcome
        }

        return url('/welcome');
    }

    public function render()
    {
        return view('livewire.splash.splash-screen')
            ->layout('layouts.user.blank', [
                'title' => ' ',
            ]);
    }
}
