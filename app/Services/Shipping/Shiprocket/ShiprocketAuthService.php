<?php

namespace App\Services\Shipping\Shiprocket;

use App\Exceptions\Shipping\ShiprocketException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShiprocketAuthService
{
    /**
     * Get the Shiprocket bearer token.
     */
    public function token(): string
    {
        $cacheKey = config('shiprocket.cache_token_key', 'shiprocket_access_token');
        $ttl = config('shiprocket.token_ttl_minutes', 13800);

        return Cache::remember($cacheKey, $ttl * 60, function () {
            return $this->refreshToken();
        });
    }

    /**
     * Force refresh the token from Shiprocket API.
     */
    public function refreshToken(): string
    {
        $email = config('shiprocket.email');
        $password = config('shiprocket.password');

        if (empty($email) || empty($password)) {
            throw new ShiprocketException("Shiprocket credentials are not configured.");
        }

        $baseUrl = rtrim(config('shiprocket.base_url'), '/');
        $url = $baseUrl . '/auth/login';

        try {
            $response = Http::timeout(config('shiprocket.timeout', 30))
                ->post($url, [
                    'email' => $email,
                    'password' => $password,
                ]);

            if ($response->failed()) {
                Log::error('Shiprocket Login Failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
                throw new ShiprocketException(
                    "Failed to login to Shiprocket: " . ($response->json()['message'] ?? 'Unknown error'),
                    $response->status(),
                    $response->json()
                );
            }

            $data = $response->json();
            $token = $data['token'] ?? $data['data']['token'] ?? $data['access_token'] ?? null;

            if (empty($token)) {
                throw new ShiprocketException("Token not found in Shiprocket login response.");
            }

            return $token;

        } catch (\Exception $e) {
            if ($e instanceof ShiprocketException) {
                throw $e;
            }
            Log::error('Shiprocket Auth Exception', ['message' => $e->getMessage()]);
            throw new ShiprocketException("Shiprocket Auth Error: " . $e->getMessage());
        }
    }

    /**
     * Forget the cached token.
     */
    public function forgetToken(): void
    {
        Cache::forget(config('shiprocket.cache_token_key', 'shiprocket_access_token'));
    }
}
