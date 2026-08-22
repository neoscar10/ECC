<?php

namespace App\Jobs\Notifications;

use App\Models\Archive\ArchiveProductEnquiry;
use App\Services\Notifications\WhatsAppNotificationSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEnquiryDeliveryRequestWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $enquiry;

    /**
     * Create a new job instance.
     */
    public function __construct(ArchiveProductEnquiry $enquiry)
    {
        $this->enquiry = $enquiry;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppNotificationSender $waSender): void
    {
        $phoneNumber = null;
        if ($this->enquiry->user && $this->enquiry->user->phone) {
            $phoneNumber = $this->enquiry->user->phone;
        } elseif ($this->enquiry->contact_phone) {
            $phoneNumber = $this->enquiry->contact_phone;
        }

        if (empty($phoneNumber)) {
            \Illuminate\Support\Facades\Log::info("WhatsApp Delivery Request Skipped: No phone number for Enquiry #{$this->enquiry->id}");
            return;
        }

        $customerName = $this->enquiry->contact_name ?? 'Valued Member';
        $productTitle = $this->enquiry->product ? $this->enquiry->product->title : 'Item Order';
        $buttonParam = (string) $this->enquiry->id;

        $waSender->sendTemplate(
            $phoneNumber,
            'archive_enquiry_delivery_request',
            [$customerName, $productTitle],
            [$buttonParam]
        );
    }
}
