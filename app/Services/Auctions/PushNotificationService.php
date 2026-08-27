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
        dispatch(new \App\Jobs\Notifications\SendFcmToUserJob(
            $user->id,
            $title,
            $body,
            $data
        ));
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
