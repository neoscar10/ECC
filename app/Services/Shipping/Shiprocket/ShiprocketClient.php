<?php

namespace App\Services\Shipping\Shiprocket;

use App\Exceptions\Shipping\ShiprocketException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShiprocketClient
{
    protected $authService;

    public function __construct(ShiprocketAuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Perform a POST request.
     */
    public function post(string $endpoint, array $payload = [], bool $authenticated = true): array
    {
        return $this->request('POST', $endpoint, $payload, $authenticated);
    }

    /**
     * Perform a GET request.
     */
    public function get(string $endpoint, array $query = [], bool $authenticated = true): array
    {
        return $this->request('GET', $endpoint, $query, $authenticated);
    }

    /**
     * Internal request wrapper.
     */
    protected function request(string $method, string $endpoint, array $data = [], bool $authenticated = true): array
    {
        $baseUrl = rtrim(config('shiprocket.base_url'), '/');
        $endpoint = ltrim($endpoint, '/');
        $url = $baseUrl . '/' . $endpoint;

        $request = Http::timeout(config('shiprocket.timeout', 30))
            ->acceptJson();

        if ($authenticated) {
            $request->withToken($this->authService->token());
        }

        try {
            $response = $method === 'POST' 
                ? $request->post($url, $data) 
                : $request->get($url, $data);

            if ($response->failed()) {
                $this->handleFailure($url, $method, $response);
            }

            return $response->json() ?? [];

        } catch (\Exception $e) {
            if ($e instanceof ShiprocketException) {
                throw $e;
            }
            Log::error("Shiprocket API Exception: {$method} {$url}", ['message' => $e->getMessage()]);
            throw new ShiprocketException("Shiprocket API Error: " . $e->getMessage());
        }
    }

    /**
     * Handle failed HTTP responses.
     */
    protected function handleFailure(string $url, string $method, $response): void
    {
        $body = $response->json();
        $message = $body['message'] ?? 'Unknown API Error';
        
        Log::error("Shiprocket API Failure: {$method} {$url}", [
            'status' => $response->status(),
            'response' => $body,
        ]);

        throw new ShiprocketException(
            "Shiprocket API Error ({$response->status()}): {$message}",
            $response->status(),
            $body
        );
    }
}
