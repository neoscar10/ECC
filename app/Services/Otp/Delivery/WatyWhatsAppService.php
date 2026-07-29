<?php

namespace App\Services\Otp\Delivery;

use App\Services\Otp\OtpDeliveryResult;
use App\Exceptions\OtpException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WatyWhatsAppService implements OtpDeliveryInterface
{
    private bool $enabled;
    private string $baseUrl;
    private string $apiToken;
    private string $otpAccount;
    private string $adminPhoneNumber;
    private int $timeout;
    private int $retryTimes;
    private int $retrySleepMs;

    public function __construct()
    {
        $this->enabled          = (bool) config('services.waty_whatsapp.enabled', true);
        $this->baseUrl          = (string) config('services.waty_whatsapp.base_url', 'https://bizlawn.storesite.in/api');
        $this->apiToken         = (string) config('services.waty_whatsapp.api_token', '');
        $this->otpAccount       = (string) config('services.waty_whatsapp.otp_account', 'mobile_app');
        $this->adminPhoneNumber = (string) config('services.waty_whatsapp.admin_phone_number', '');
        $this->timeout          = (int) config('services.waty_whatsapp.timeout', 15);
        $this->retryTimes       = (int) config('services.waty_whatsapp.retry_times', 2);
        $this->retrySleepMs     = (int) config('services.waty_whatsapp.retry_sleep_ms', 200);
    }

    /**
     * Send an OTP via Waty WhatsApp API.
     *
     * @param string $phone E.164 formatted phone number.
     * @param string $otp   Plaintext 6-digit OTP.
     * @param string|null $templateName Custom template name (ignored by Waty, used if needed).
     * @return OtpDeliveryResult
     * @throws OtpException If delivery fails catastrophically.
     */
    public function sendOtp(string $phone, string $otp, ?string $templateName = null): OtpDeliveryResult
    {
        // Guard: Check if service enabled
        if (!$this->enabled) {
            Log::warning('WatyWhatsApp: Service disabled via config. OTP delivery skipped.', [
                'phone_last4' => substr($phone, -4),
            ]);

            return OtpDeliveryResult::skipped('waty_whatsapp', 'Waty WhatsApp service is disabled.');
        }

        // Guard: Check credentials
        if (empty($this->apiToken) || empty($this->otpAccount) || empty($this->adminPhoneNumber)) {
            Log::warning('WatyWhatsApp: Missing credentials or admin phone number. OTP delivery skipped.', [
                'phone_last4' => substr($phone, -4),
                'has_api_token' => !empty($this->apiToken),
                'has_otp_account' => !empty($this->otpAccount),
                'has_admin_phone' => !empty($this->adminPhoneNumber),
            ]);

            return OtpDeliveryResult::skipped('waty_whatsapp', 'Waty WhatsApp credentials not configured.');
        }

        $url = rtrim($this->baseUrl, '/') . '/otp/send';

        $payload = [
            'api_token'          => $this->apiToken,
            'otp_account'        => $this->otpAccount,
            'phone_number'       => $phone,
            'otp_code'           => $otp,
            'admin_phone_number' => $this->adminPhoneNumber,
        ];

        Log::debug('WatyWhatsApp: Sending OTP Payload:', [
            'url' => $url,
            'otp_account' => $this->otpAccount,
            'phone_last4' => substr($phone, -4),
        ]);

        try {
            $response = Http::asJson()
                ->timeout($this->timeout)
                ->retry($this->retryTimes, $this->retrySleepMs, function (\Exception $exception, $request) {
                    if ($exception instanceof \Illuminate\Http\Client\ConnectionException) {
                        return true;
                    }

                    if ($exception instanceof \Illuminate\Http\Client\RequestException) {
                        return $exception->response->status() >= 500;
                    }

                    return false;
                })
                ->post($url, $payload);

        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('WatyWhatsApp: RequestException caught during API call.', [
                'phone_last4' => substr($phone, -4),
                'status' => $e->response?->status(),
                'body' => $e->response?->json() ?? $e->response?->body(),
            ]);
            return $this->handleFailedResponse($e->response, $phone);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('WatyWhatsApp: Connection failure after retries.', [
                'phone_last4' => substr($phone, -4),
                'error' => $e->getMessage(),
            ]);

            throw new OtpException(
                'Unable to reach Waty WhatsApp service. Please try again shortly.',
                503,
                $e
            );
        } catch (\Exception $e) {
            Log::error('WatyWhatsApp: Unexpected exception during API call.', [
                'phone_last4' => substr($phone, -4),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        if ($response->failed()) {
            return $this->handleFailedResponse($response, $phone);
        }

        $data = $response->json();
        $isSuccess = (bool) ($data['success'] ?? false);

        if (!$isSuccess) {
            $failureReason = $data['message'] ?? 'Waty WhatsApp API returned failure status.';
            Log::warning('WatyWhatsApp: API returned success=false', [
                'phone_last4' => substr($phone, -4),
                'message' => $failureReason,
            ]);

            return OtpDeliveryResult::failure('waty_whatsapp', $failureReason, false);
        }

        $messageId = $data['message_id'] ?? ('waty_' . Str::random(12));

        Log::info('WatyWhatsApp: Delivered successfully.', [
            'phone_last4' => substr($phone, -4),
            'waty_message_id' => $messageId,
        ]);

        return OtpDeliveryResult::success('waty_whatsapp', $messageId);
    }

    /**
     * Fetch OTP settings for the configured account.
     *
     * @return array
     */
    public function getSettings(): array
    {
        if (empty($this->apiToken) || empty($this->otpAccount)) {
            return [
                'success' => false,
                'message' => 'Waty WhatsApp credentials not configured.',
            ];
        }

        $url = rtrim($this->baseUrl, '/') . '/otp/settings';

        try {
            $response = Http::timeout($this->timeout)
                ->get($url, [
                    'api_token' => $this->apiToken,
                    'otp_account' => $this->otpAccount,
                ]);

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error('WatyWhatsApp: Failed to fetch settings.', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle failed HTTP responses from Waty API.
     */
    private function handleFailedResponse(\Illuminate\Http\Client\Response $response, string $phone): OtpDeliveryResult
    {
        $status = $response->status();
        $body = $response->json() ?? [];
        $message = $body['message'] ?? 'Unknown Waty API error';

        Log::error('WatyWhatsApp: API returned error response.', [
            'phone_last4' => substr($phone, -4),
            'http_status' => $status,
            'response_body' => $body,
        ]);

        // Auth Error (401)
        if ($status === 401) {
            throw new OtpException(
                'Waty WhatsApp API authentication failed. Invalid API token.',
                401
            );
        }

        // Unprocessable Entity (422)
        if ($status === 422) {
            $errors = $body['errors'] ?? [];
            $errorMsg = !empty($errors) ? json_encode($errors) : $message;
            return OtpDeliveryResult::failure(
                provider: 'waty_whatsapp',
                reason: 'Waty WhatsApp API validation failed: ' . $errorMsg,
                retryable: false
            );
        }

        // Rate Limited (429)
        if ($status === 429) {
            return OtpDeliveryResult::failure(
                provider: 'waty_whatsapp',
                reason: 'Rate limited by Waty API. Too many requests.',
                retryable: true
            );
        }

        // Server Errors (5xx)
        if ($status >= 500) {
            return OtpDeliveryResult::failure(
                provider: 'waty_whatsapp',
                reason: 'Waty server error (HTTP ' . $status . '): ' . $message,
                retryable: true
            );
        }

        return OtpDeliveryResult::failure(
            provider: 'waty_whatsapp',
            reason: 'Unexpected Waty API error (HTTP ' . $status . '): ' . $message,
            retryable: false
        );
    }
}
