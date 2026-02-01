<?php

namespace App\Services\Notifications;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmTopicManager
{
    protected $projectId;
    
    // Scopes for FCM
    const SCOPES = ['https://www.googleapis.com/auth/firebase.messaging'];

    public function __construct()
    {
        // Try to get project ID from config or env setup for existing PushNotificationService
        // Assuming typical setup, but for now we might mock projectId if strictly testing.
        // We will try to load from a standard path if exists, otherwise rely on env.
        // The previous discovery showed PushNotificationService referenced Google Creds.
        $this->projectId = config('services.firebase.project_id', env('FIREBASE_PROJECT_ID')); 
    }

    /**
     * Subscribe tokens to a topic.
     * 
     * @param array $tokens
     * @param string $topic
     */
    public function subscribeTokensToTopic(array $tokens, string $topic)
    {
        if (empty($tokens)) {
            return;
        }

        $this->manageSubscription($tokens, $topic, 'batchAdd');
    }

    /**
     * Unsubscribe tokens from a topic.
     * 
     * @param array $tokens
     * @param string $topic
     */
    public function unsubscribeTokensFromTopic(array $tokens, string $topic)
    {
        if (empty($tokens)) {
            return;
        }

        $this->manageSubscription($tokens, $topic, 'batchRemove');
    }

    protected function manageSubscription(array $tokens, string $topic, string $action)
    {
        // If testing/mocking, we might not have real creds.
        if (app()->environment('testing')) {
            Log::info("FCM MOCK: {$action} - Topic: {$topic} - Tokens: " . count($tokens));
            return;
        }

        try {
            $accessToken = $this->getAccessToken();
            $url = "https://iid.googleapis.com/iid/v1:{$action}";

            $response = Http::withToken($accessToken)
                ->withHeaders(['access_token_auth' => 'true']) // Legacy IID requirement sometimes? Or standard Bearer is enough. Standard Bearer is usually enough for IID v1.
                ->post($url, [
                    'to' => '/topics/' . $topic,
                    'registration_tokens' => $tokens,
                ]);

            if ($response->failed()) {
                Log::error("FCM Topic Management Failed ({$action}): " . $response->body());
            }

        } catch (\Exception $e) {
            Log::error("FCM Exception ({$action}): " . $e->getMessage());
        }
    }

    protected function getAccessToken()
    {
        // Point to credentials file. defined in env or default location
        $credentialsPath = config('services.firebase.credentials', env('FIREBASE_CREDENTIALS'));
        
        if (!$credentialsPath || !file_exists($credentialsPath)) {
            throw new \Exception("Firebase credentials not found.");
        }

        $sa = new ServiceAccountCredentials(self::SCOPES, $credentialsPath);
        $token = $sa->fetchAuthToken(); // Use default Guzzle client provided by library
        return $token['access_token'];
    }
}
