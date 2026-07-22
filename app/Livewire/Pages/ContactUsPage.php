<?php

namespace App\Livewire\Pages;

use Livewire\Component;
use App\Models\ContactMessage;
use Livewire\Attributes\Layout;

#[Layout('layouts.web-app')]
class ContactUsPage extends Component
{
    public $name = '';
    public $email = '';
    public $subject = '';
    public $message = '';

    public $successMessage = false;

    protected $rules = [
        'name' => 'required|min:2',
        'email' => 'required|email',
        'subject' => 'nullable|string|max:255',
        'message' => 'required|min:10',
    ];

    public function submit()
    {
        $this->validate();

        ContactMessage::create([
            'name' => $this->name,
            'email' => $this->email,
            'subject' => $this->subject,
            'message' => $this->message,
        ]);

        $this->reset(['name', 'email', 'subject', 'message']);
        $this->successMessage = true;
    }

    public function render()
    {
        return view('livewire.pages.contact-us-page');
    }
}
