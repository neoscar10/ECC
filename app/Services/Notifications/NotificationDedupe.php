<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationDedupe
{
    /**
     * Check if a notification with the given dedupe key has already been sent.
     * 
     * @param string $dedupeKey
     * @return bool
     */
    public function alreadySent(string $dedupeKey): bool
    {
        return DB::table('notification_delivery_logs')
            ->where('dedupe_key', $dedupeKey)
            ->exists();
    }

    /**
     * Mark a notification as sent.
     * 
     * @param string $dedupeKey
     * @param string $type
     * @param int|null $lotId
     * @param int|null $userId
     * @param array $meta
     * @return void
     */
    public function markSent(string $dedupeKey, string $type, ?int $lotId, ?int $userId, array $meta = []): void
    {
        try {
            DB::table('notification_delivery_logs')->insertOrIgnore([
                'dedupe_key' => $dedupeKey,
                'type' => $type,
                'auction_lot_id' => $lotId,
                'user_id' => $userId,
                'meta' => json_encode($meta),
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Should not happen with insertOrIgnore, but safe catch
            Log::warning("NotificationDedupe Error: " . $e->getMessage());
        }
    }
}
