<?php

namespace App\Jobs\Notifications;

use App\Models\Archive\ArchiveProductEnquiry;
use App\Services\Notifications\WhatsAppNotificationSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEnquiryPaymentLinkWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $enquiry;
    public $checkoutUrl;

    /**
     * Create a new job instance.
     */
    public function __construct(ArchiveProductEnquiry $enquiry, string $checkoutUrl)
    {
        $this->enquiry = $enquiry;
        $this->checkoutUrl = $checkoutUrl;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppNotificationSender $waSender): void
    {
        // For WhatsApp, we need a phone number. 
        // If the enquiry has a user with a phone number, or a contact_phone, use it.
        $phoneNumber = null;
        if ($this->enquiry->user && $this->enquiry->user->phone) {
            $phoneNumber = $this->enquiry->user->phone;
        } elseif ($this->enquiry->contact_phone) {
            $phoneNumber = $this->enquiry->contact_phone;
        }

        if (empty($phoneNumber)) {
            \Illuminate\Support\Facades\Log::info("WhatsApp Payment Link Skipped: No phone number available for Enquiry #{$this->enquiry->id}");
            return;
        }

        $productTitle = $this->enquiry->product ? $this->enquiry->product->title : 'your item';
        
        $title = "Payment Requested for {$productTitle}";
        $body = "Dear {$this->enquiry->contact_name},\n\nA payment of ₹" . number_format($this->enquiry->payment_amount, 2) . " has been requested for your enquiry regarding {$productTitle}.\n\nPlease complete your payment securely using the following link:\n{$this->checkoutUrl}";
        
        // This invokes the mock WhatsApp sender we previously implemented
        $waSender->sendRaw(
            $phoneNumber,
            $title,
            $body,
            [
                'enquiry_id' => $this->enquiry->id,
                'checkout_url' => $this->checkoutUrl,
                'amount' => $this->enquiry->payment_amount,
            ]
        );
    }
}
