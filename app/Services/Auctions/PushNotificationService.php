<?php

namespace App\Services\Auctions;

use App\Models\User;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    /**
     * Send a multicast message to a user's devices.
     */
    public function sendToUser(User $user, string $title, string $body, array $data = [])
    {
        $tokens = $user->deviceTokens()->pluck('token')->toArray();
        if (empty($tokens)) {
            return;
        }

        // Ideally use kreait/laravel-firebase, but here is a raw implementation or placeholder
        // dispatch job or call API directly.
        
        // For this implementation, let's assume we dispatch a job to handle the actual API call
        // to avoid blocking the request.
        
        // \App\Jobs\SendFcmMessage::dispatch($tokens, $title, $body, $data);
        
        // Simulating logic for the service file:
        Log::info("Sending Push to User {$user->id}: {$title} - {$body}");
    }
    
    public function notifyOutbid(User $user, $lotTitle, $lotId)
    {
        $this->sendToUser(
            $user, 
            "You've been outbid!", 
            "Someone placed a higher bid on '{$lotTitle}'. Bid again now!",
            ['type' => 'outbid', 'lot_id' => $lotId]
        );
    }
    
    public function notifyEndingSoon(User $user, $lotTitle, $lotId)
    {
        $this->sendToUser(
            $user, 
            "Auction Ending Soon", 
            "'{$lotTitle}' is ending in 5 minutes.",
            ['type' => 'ending_soon', 'lot_id' => $lotId]
        );
    }
}
