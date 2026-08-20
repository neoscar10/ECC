<?php

namespace App\Mail\Archive;

use App\Models\Archive\ArchiveProductEnquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeliveryDetailsRequestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $enquiry;

    /**
     * Create a new message instance.
     */
    public function __construct(ArchiveProductEnquiry $enquiry)
    {
        $this->enquiry = $enquiry;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $productTitle = $this->enquiry->product ? $this->enquiry->product->title : 'your item';
        return new Envelope(
            subject: 'Delivery Details Required for ' . $productTitle,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.archive.delivery_details_request',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
