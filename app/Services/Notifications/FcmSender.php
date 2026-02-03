<?php

namespace App\Services\Notifications;

use App\Models\User;
use App\Models\UserDeviceToken;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmSender
{
    protected $projectId;
    const SCOPES = ['https://www.googleapis.com/auth/firebase.messaging'];

    public function __construct()
    {
        $this->projectId = config('services.firebase.project_id', env('FIREBASE_PROJECT_ID'));
        
        // Fallback: extract from credentials file if possible
        if (empty($this->projectId)) {
             $credentialsPath = config('services.firebase.credentials', env('FIREBASE_CREDENTIALS'));
             if ($credentialsPath && file_exists($credentialsPath)) {
                 $json = json_decode(file_get_contents($credentialsPath), true);
                 $this->projectId = $json['project_id'] ?? null;
             }
        }
    }

    /**
     * Normalize data payload to ensure flat string-to-string map for FCM HTTP v1.
     * 
     * @param array $data
     * @return array
     */
    protected function normalizeData(array $data): array
    {
        $normalized = [];
        foreach ($data as $key => $value) {
            if (is_null($value)) {
                continue; // Remove null keys
            }
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif (is_array($value) || is_object($value)) {
                $value = json_encode($value); // Flatten complex structures
            }
            // Cast to string
            $normalized[(string)$key] = (string)$value;
        }
        return $normalized;
    }
    
    /**
     * Normalize options to allow only valid FCM V1 keys.
     * 
     * @param array $options
     * @return array
     */
    protected function normalizeOptions(array $options): array
    {
        $validKeys = ['android', 'apns', 'webpush', 'fcm_options'];
        return array_intersect_key($options, array_flip($validKeys));
    }

    /**
     * Send notification to a specific topic.
     * 
     * @param string $topic
     * @param string $title
     * @param string $body
     * @param array $data
     * @param array $options
     * @return void
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = [], array $options = []): void
    {
        // 1. Normalize Input
        $normalizedData = !empty($data) ? $this->normalizeData($data) : null;
        $validOptions = $this->normalizeOptions($options);

        // 2. Log Attempt
        Log::info('FCM_SEND_TOPIC', [
            'action' => 'FCM_SEND_TOPIC',
            'status' => 'attempt',
            'topic' => $topic,
            'title' => $title,
            'body' => $body,
            'data' => $normalizedData,
            'options' => $validOptions // Log the filtered options
        ]);

        // 3. Build Payload
        $message = array_merge([
            'topic' => $topic,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $normalizedData,
        ], $validOptions);
        
        if (empty($message['data'])) {
            unset($message['data']);
        }

        // 4. Send via HTTP v1
        $this->sendRaw($message, 'topic:' . $topic);
    }

    /**
     * Send notification to a specific user (send to all their active tokens).
     * 
     * @param User $user
     * @param string $title
     * @param string $body
     * @param array $data
     * @param array $options
     * @return void
     */
    public function sendToUser(User $user, string $title, string $body, array $data = [], array $options = []): void
    {
        $tokens = $user->deviceTokens()->where('is_active', true)->pluck('token')->toArray();
        if (empty($tokens)) { 
             Log::info("FCM_SEND_USER skipped: No active tokens", ['user_id' => $user->id]);
             return; 
        }

        $validOptions = $this->normalizeOptions($options);
        
        // Log Attempt
        Log::info('FCM_SEND_USER', [
            'action' => 'FCM_SEND_USER',
            'status' => 'attempt',
            'user_id' => $user->id,
            'tokens_count' => count($tokens),
            'title' => $title,
            'body' => $body,
            'data' => !empty($data) ? $this->normalizeData($data) : null,
            'options' => $validOptions
        ]);

        $this->sendToTokens($tokens, $title, $body, $data, $options);
    }

    /**
     * Send notification to a specific user's TOPIC.
     * This is an alternative to sendToUser (which loops tokens) and relies on the user being subscribed to their topic.
     * 
     * @param int $userId
     * @param string $title
     * @param string $body
     * @param array $data
     * @param array $options
     * @return void
     */
    public function sendToUserTopic(int $userId, string $title, string $body, array $data = [], array $options = []): void
    {
        $topic = \App\Support\Notifications\FcmTopicNamer::userTopic($userId);
        $this->sendToTopic($topic, $title, $body, $data, $options);
    }

    /**
     * Send notification to a list of tokens.
     * 
     * @param array $tokens
     * @param string $title
     * @param string $body
     * @param array $data
     * @param array $options
     * @return void
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = [], array $options = []): void
    {
        $tokens = array_unique(array_filter($tokens));
        if (empty($tokens)) {
            return;
        }
        
        $normalizedData = !empty($data) ? $this->normalizeData($data) : null;
        $validOptions = $this->normalizeOptions($options);

        // FCM HTTP v1 does not support multicast "registration_ids" like legacy. 
        // We must loop or use batch endpoint manually if available.
        foreach ($tokens as $token) {
            $message = array_merge([
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $normalizedData,
            ], $validOptions);

             if (empty($message['data'])) {
                unset($message['data']);
            }

            $success = $this->sendRaw($message, 'token:' . substr($token, 0, 10));
            
            if (!$success) {
                // Determine if 404/410/Invalid -> mark inactive
                // sendRaw logic handles marking logic or returning status
            }
        }
    }
    
    // Better: Helper to allow tests to disable the short-circuit.
    public static $fakeInTesting = true;

    protected function sendRaw(array $message, string $contextLog)
    {
        if (self::$fakeInTesting && app()->environment('testing')) {
            Log::info("FCM MOCK SEND: {$contextLog}");
            return true;
        }

        // Safety check for Project ID
        if (empty($this->projectId)) {
             Log::error("FCM Send Failed: Project ID is missing. Check .env or services.php or credentials file.");
             return false;
        }

        try {
            $accessToken = $this->getAccessToken();
            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, ['message' => $message]);

            if ($response->successful()) {
                Log::info("FCM Sent Success: {$contextLog}");
                return true;
            } else {
                // Log full request payload for debugging
                Log::warning("FCM Failed Request Payload: " . json_encode($message));
                
                $error = $response->json('error');
                $code = $error['code'] ?? $response->status();
                $msg = $error['message'] ?? 'Unknown error';
                $details = $error['details'] ?? [];

                Log::warning("FCM Send Failed [{$code}]: {$msg}", [
                    'context' => $contextLog,
                    'details' => $details
                ]);

                // Handle Invalid Token Cleanup
                if (isset($message['token']) && in_array($code, [404, 410])) { // UNREGISTERED or GONE
                    $this->markTokenInactive($message['token']);
                    Log::info("Marked token inactive: " . substr($message['token'], 0, 10));
                }
                
                // Specific error strings sometimes come in 400 Bad Request too
                if (str_contains(strtolower($msg), 'registration token is not a valid fcm')) {
                     $this->markTokenInactive($message['token'] ?? '');
                }

                return false;
            }

        } catch (\Exception $e) {
            Log::error("FCM Exception: " . $e->getMessage());
            return false;
        }
    }

    protected function markTokenInactive(?string $token)
    {
        if (!$token) return;
        UserDeviceToken::where('token', $token)->update(['is_active' => false]);
    }

    protected function getAccessToken()
    {
        // Reused from FcmTopicManager
        $credentialsPath = config('services.firebase.credentials', env('FIREBASE_CREDENTIALS'));
        
        if (!$credentialsPath || !file_exists($credentialsPath)) {
            throw new \Exception("Firebase credentials not found.");
        }

        $sa = new ServiceAccountCredentials(self::SCOPES, $credentialsPath);
        $token = $sa->fetchAuthToken(); // Use default Guzzle client
        return $token['access_token'];
    }
}
