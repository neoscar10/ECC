<?php

namespace App\Services\Archive;

use App\Models\User;
use App\Models\Archive\ArchiveProductEnquiry;
use Illuminate\Support\Facades\Log;

class ArchiveEnquiryService
{
    /**
     * Create a new archive product enquiry for a user.
     *
     * @param User $user
     * @param int $archiveProductId
     * @param string|null $message
     * @return ArchiveProductEnquiry
     */
    public function createEnquiry($user, int $archiveProductId, ?string $message): ArchiveProductEnquiry
    {
        $enquiry = ArchiveProductEnquiry::create([
            'user_id' => $user->id,
            'archive_product_id' => $archiveProductId,
            'message' => $message,
            'contact_email' => $user->email,
            'contact_phone' => $user->phone ?? null,
            'contact_name' => $user->name,
            'status' => 'new',
        ]);

        if ($email = env('ARCHIVE_ENQUIRY_NOTIFY_EMAIL')) {
             Log::info("Enquiry received for product #{$archiveProductId}. Notification would go to $email");
        }

        return $enquiry;
    }
}
