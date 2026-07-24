<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public ContactMessage $contactMessage;

    /**
     * Create a new message instance.
     */
    public function __construct(ContactMessage $contactMessage)
    {
        $this->contactMessage = $contactMessage;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subjectText = $this->contactMessage->subject
            ? 'New Contact Inquiry: ' . $this->contactMessage->subject
            : 'New Contact Inquiry from ' . $this->contactMessage->name;

        return new Envelope(
            subject: $subjectText,
            replyTo: [
                new Address($this->contactMessage->email, $this->contactMessage->name)
            ]
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-message-received',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
