<?php

namespace App\Mail;

use App\Models\MembershipApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MembershipApplicationApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $application;

    public function __construct(MembershipApplication $application)
    {
        $this->application = $application;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Membership Application Approved - Executive Club Cricket',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.membership.approved',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
