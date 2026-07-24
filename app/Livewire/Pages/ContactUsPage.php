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

        $contactMsg = ContactMessage::create([
            'name' => $this->name,
            'email' => $this->email,
            'subject' => $this->subject,
            'message' => $this->message,
        ]);

        // Send email to platform contact email
        try {
            $config = \App\Models\ContactConfig::first();
            $recipientEmail = $config?->support_email ?: config('mail.from.address');

            if ($recipientEmail) {
                \Illuminate\Support\Facades\Mail::to($recipientEmail)->send(new \App\Mail\ContactMessageReceivedMail($contactMsg));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send contact inquiry email: ' . $e->getMessage());
        }

        $this->reset(['name', 'email', 'subject', 'message']);
        $this->successMessage = true;
    }

    public function render()
    {
        $contactConfig = \App\Models\ContactConfig::first();
        return view('livewire.pages.contact-us-page', [
            'contactConfig' => $contactConfig
        ]);
    }
}
