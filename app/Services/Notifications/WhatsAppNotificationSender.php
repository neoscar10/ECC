<?php

namespace App\Services\Notifications;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Models\Auctions\AuctionNotificationSubscription;

class WhatsAppNotificationSender
{
    protected $enabled;
    protected $phoneNumberId;
    protected $accessToken;
    protected $defaultTemplate;

    public function __construct()
    {
        $this->enabled = config('services.whatsapp.enabled', env('WHATSAPP_ENABLED', true));
        $this->phoneNumberId = config('services.whatsapp.phone_number_id', env('WHATSAPP_PHONE_NUMBER_ID'));
        $this->accessToken = config('services.whatsapp.access_token', env('WHATSAPP_ACCESS_TOKEN'));
        $this->defaultTemplate = config('services.whatsapp.template_name', env('WHATSAPP_TEMPLATE_NAME', 'welcome_message'));
    }

    /**
     * Send a WhatsApp notification to a specific user.
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $user = User::find($userId);
        
        if (!$user || empty($user->phone)) {
            Log::info("WhatsApp Notification Skipped: User not found or no phone number", ['user_id' => $userId]);
            return;
        }

        $this->sendRaw($user->phone, $title, $body, $data);
    }

    /**
     * Send a WhatsApp notification to all users subscribed to a specific topic.
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): void
    {
        if (!$this->enabled) {
            return;
        }

        $userIds = [];

        if (preg_match('/^ecc_auction_(\d+)$/', $topic, $matches)) {
            $lotId = $matches[1];
            $userIds = AuctionNotificationSubscription::where('auction_lot_id', $lotId)
                            ->where('is_enabled', true)
                            ->pluck('user_id')
                            ->toArray();
        } elseif ($topic === 'ecc_all_users') {
            // Be careful with this in production!
            $userIds = User::whereNotNull('phone')->pluck('id')->toArray();
        } elseif (preg_match('/^ecc_membership_(\d+)$/', $topic, $matches)) {
            $tierId = $matches[1];
            $userIds = User::where('membership_tier_id', $tierId)->pluck('id')->toArray();
        } elseif (preg_match('/^ecc_user_(\d+)$/', $topic, $matches)) {
            $userIds = [$matches[1]];
        } else {
            Log::warning("WhatsApp Notification Skipped: Unrecognized topic format", ['topic' => $topic]);
            return;
        }

        $userIds = array_unique($userIds);

        if (empty($userIds)) {
            return;
        }

        // Chunking to prevent memory issues for large topics
        foreach (array_chunk($userIds, 100) as $chunk) {
            $users = User::whereIn('id', $chunk)->whereNotNull('phone')->get();
            foreach ($users as $user) {
                $this->sendRaw($user->phone, $title, $body, $data);
            }
        }
    }

    /**
     * Core sending logic (Mocked for now).
     */
    public function sendRaw(string $phoneNumber, string $title, string $body, array $data): void
    {
        // Format the phone number properly (strip +, -, spaces)
        $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);

        // When templates and credentials are ready, this Log::info will be replaced with an HTTP request to Meta API.
        Log::info("WhatsApp Mock Send:", [
            'phone_number' => $cleanPhone,
            'template'     => $this->defaultTemplate,
            'title'        => $title,
            'body'         => $body,
            'data'         => $data
        ]);
        
        /* 
        // FUTURE META API IMPLEMENTATION
        try {
            $response = \Illuminate\Support\Facades\Http::withToken($this->accessToken)
                ->post("https://graph.facebook.com/v17.0/{$this->phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $cleanPhone,
                    'type' => 'template',
                    'template' => [
                        'name' => $this->defaultTemplate,
                        'language' => ['code' => config('services.whatsapp.template_language', 'en_US')],
                        'components' => [
                            // Variables will go here when template is defined
                        ]
                    ]
                ]);
                
            if (!$response->successful()) {
                Log::error("WhatsApp API Error: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("WhatsApp Exception: " . $e->getMessage());
        }
        */
    }
}
