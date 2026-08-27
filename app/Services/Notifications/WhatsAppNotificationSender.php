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
        $this->enabled = (bool) (env('WHATSAPP_ENABLED', true) && (config('services.whatsapp.enabled', true) || config('services.waty_whatsapp.enabled', true)));
        $this->phoneNumberId = (string) config('services.whatsapp.phone_number_id', env('WHATSAPP_PHONE_NUMBER_ID', ''));
        $this->accessToken = (string) config('services.whatsapp.access_token', env('WHATSAPP_ACCESS_TOKEN', ''));
        $this->defaultTemplate = (string) config('services.whatsapp.template_name', env('WHATSAPP_TEMPLATE_NAME', 'welcome_message'));
    }

    /**
     * Send a WhatsApp notification to a specific user.
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = []): void
    {
        if (!$this->enabled) {
            Log::info("WhatsApp Notification Skipped (Service Disabled)", ['user_id' => $userId]);
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
     * Send a direct text WhatsApp message via Meta Cloud API.
     */
    public function sendRawTextMessage(string $phoneNumber, string $message): bool
    {
        if (!$this->enabled || empty($this->accessToken) || empty($this->phoneNumberId)) {
            Log::warning("WhatsApp Direct Text Skipped (Unconfigured or Disabled)", ['phone' => $phoneNumber]);
            return false;
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);
        if (strlen($cleanPhone) === 10) {
            $defaultPrefix = config('services.whatsapp.default_country_prefix', '91');
            $cleanPhone = $defaultPrefix . $cleanPhone;
        }

        $apiVersion = config('services.whatsapp.api_version', 'v22.0');
        $url = "https://graph.facebook.com/{$apiVersion}/{$this->phoneNumberId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $cleanPhone,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $message,
            ],
        ];

        Log::info("WhatsApp Direct Text Dispatch Initiated", [
            'phone' => $cleanPhone,
            'message' => $message,
        ]);

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($this->accessToken)
                ->timeout(config('services.whatsapp.timeout', 15))
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info("WhatsApp Direct Text Dispatched Successfully", [
                    'phone' => $cleanPhone,
                    'status' => $response->status(),
                    'message_id' => $response->json('messages.0.id'),
                ]);
                return true;
            }

            Log::error("WhatsApp Direct Text API Error Response", [
                'phone' => $cleanPhone,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error("WhatsApp Direct Text Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send a pre-approved Meta WhatsApp Template message.
     */
    public function sendTemplate(string $phoneNumber, string $templateName, array $bodyVariables = [], array $buttonVariables = [], string $language = null): bool
    {
        $language = $language ?? config('services.whatsapp.template_language', 'en');
        if (!$this->enabled) {
            Log::info("WhatsApp Notification Skipped (Disabled in Config)", ['phone' => $phoneNumber, 'template' => $templateName]);
            return false;
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);

        if (strlen($cleanPhone) === 10) {
            $defaultPrefix = config('services.whatsapp.default_country_prefix', '91');
            $cleanPhone = $defaultPrefix . $cleanPhone;
        }

        $apiVersion = config('services.whatsapp.api_version', 'v22.0');

        $components = [];

        if (!empty($bodyVariables)) {
            $bodyParams = [];
            foreach ($bodyVariables as $var) {
                $bodyParams[] = ['type' => 'text', 'text' => (string) $var];
            }
            $components[] = [
                'type' => 'body',
                'parameters' => $bodyParams,
            ];
        }

        if (!empty($buttonVariables)) {
            $btnParams = [];
            foreach ($buttonVariables as $var) {
                $btnParams[] = ['type' => 'text', 'text' => (string) $var];
            }
            $components[] = [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => '0',
                'parameters' => $btnParams,
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $cleanPhone,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $language],
            ],
        ];

        if (!empty($components)) {
            $payload['template']['components'] = $components;
        }

        Log::info("WhatsApp Template Dispatch Initiated", [
            'raw_phone' => $phoneNumber,
            'clean_phone' => $cleanPhone,
            'template' => $templateName,
            'payload' => $payload,
        ]);

        try {
            $url = "https://graph.facebook.com/{$apiVersion}/{$this->phoneNumberId}/messages";
            $response = \Illuminate\Support\Facades\Http::withToken($this->accessToken)
                ->timeout(config('services.whatsapp.timeout', 15))
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info("WhatsApp Template Dispatched Successfully", [
                    'phone' => $cleanPhone,
                    'template' => $templateName,
                    'status' => $response->status(),
                    'message_id' => $response->json('messages.0.id'),
                ]);
                return true;
            }

            Log::error("WhatsApp Template API Error Response", [
                'phone' => $cleanPhone,
                'template' => $templateName,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error("WhatsApp Template Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Core sending logic.
     */
    public function sendRaw(string $phoneNumber, string $title, string $body, array $data): void
    {
        // If an explicit template name is provided (e.g. archive_enquiry_delivery_request)
        if (!empty($data['template'])) {
            $bodyVars = $data['body_vars'] ?? [$title, $body];
            $buttonVars = $data['button_vars'] ?? [];
            $this->sendTemplate($phoneNumber, $data['template'], $bodyVars, $buttonVars);
            return;
        }

        // For dynamic notifications (Outbid, Bid Placed, Winner, Reminder), format text message
        $formattedText = "*{$title}*\n\n{$body}";

        // Attempt direct text delivery first
        $sentDirect = $this->sendRawTextMessage($phoneNumber, $formattedText);

        // Fallback to default template if direct text failed and default template exists
        if (!$sentDirect && !empty($this->defaultTemplate)) {
            $this->sendTemplate($phoneNumber, $this->defaultTemplate, [$title, $body]);
        }
    }
}
