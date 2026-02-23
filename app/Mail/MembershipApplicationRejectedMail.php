<?php

namespace App\Mail;

use App\Models\MembershipApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MembershipApplicationRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $application;
    public $reason;

    public function __construct(MembershipApplication $application, $reason = '')
    {
        $this->application = $application;
        $this->reason = $reason;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Update on Your Membership Application - Executive Club Cricket',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.membership.rejected',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
