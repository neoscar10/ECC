<?php

namespace App\Livewire\Admin\Settings;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ChangePassword extends Component
{
    public $current_password = '';
    public $password = '';
    public $password_confirmation = '';

    public function rules()
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function updatePassword()
    {
        $this->validate();

        $user = Auth::user();

        $user->update([
            'password' => Hash::make($this->password),
        ]);

        $this->reset(['current_password', 'password', 'password_confirmation']);

        session()->flash('success', 'Your password has been changed successfully.');
    }

    public function render()
    {
        return view('livewire.admin.settings.change-password')
            ->layout('layouts.admin', ['title' => 'Change Password']);
    }
}
