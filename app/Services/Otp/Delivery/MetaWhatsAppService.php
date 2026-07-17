<?php

namespace App\Services\Otp\Delivery;

use App\Services\Otp\OtpDeliveryResult;
use App\Exceptions\OtpException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaWhatsAppService implements OtpDeliveryInterface
{
    private string $baseUrl;
    private string $phoneNumberId;
    private string $accessToken;
    private string $templateName;
    private string $templateLanguage;
    private int $timeout;
    private int $retryTimes;
    private int $retrySleepMs;
    private bool $enabled;
    private bool $templateHasVariables;
    private ?string $templateVariableName;
    private bool $sendRawText;

    public function __construct()
    {
        $this->enabled         = (bool) config('services.whatsapp.enabled', false);
        $this->accessToken     = (string) config('services.whatsapp.access_token', '');
        $this->phoneNumberId   = (string) config('services.whatsapp.phone_number_id', '');
        $this->templateName    = (string) config('services.whatsapp.template_name', 'authentication');
        $this->templateLanguage = (string) config('services.whatsapp.template_language', 'en_US');
        $this->templateHasVariables = (bool) config('services.whatsapp.template_has_variables', true);
        $this->templateVariableName = config('services.whatsapp.template_variable_name');
        $this->sendRawText     = (bool) config('services.whatsapp.send_raw_text', false);
        $this->timeout         = (int) config('services.whatsapp.timeout', 15);
        $this->retryTimes      = (int) config('services.whatsapp.retry_times', 2);
        $this->retrySleepMs    = (int) config('services.whatsapp.retry_sleep_ms', 200);

        $apiVersion = config('services.whatsapp.api_version', 'v22.0');
        $this->baseUrl = "https://graph.facebook.com/{$apiVersion}";
    }

    /**
     * Send an OTP via WhatsApp Cloud API.
     *
     * @param string $phone E.164 formatted phone number.
     * @param string $otp   Plaintext 6-digit OTP.
     * @param string|null $templateName Custom template name.
     * @return OtpDeliveryResult
     * @throws OtpException If delivery fails catastrophically.
     */
    public function sendOtp(string $phone, string $otp, ?string $templateName = null): OtpDeliveryResult
    {
        // ── Guard: Skip if WhatsApp is disabled or unconfigured ──
        if (!$this->enabled) {
            Log::warning('MetaWhatsApp: Service disabled via config. OTP delivery skipped.', [
                'phone_last4' => substr($phone, -4),
            ]);

            return OtpDeliveryResult::skipped('whatsapp', 'WhatsApp service is disabled.');
        }

        if (config('services.whatsapp.otp_method') === 'direct_message') {
            Log::info('MetaWhatsApp: OTP method is direct_message. Skipping template delivery, waiting for user message.', [
                'phone_last4' => substr($phone, -4),
            ]);
            return OtpDeliveryResult::skipped('whatsapp', 'Waiting for user direct message.');
        }

        if (empty($this->accessToken) || empty($this->phoneNumberId)) {
            Log::warning('MetaWhatsApp: Missing credentials. OTP delivery skipped.', [
                'phone_last4' => substr($phone, -4),
            ]);

            return OtpDeliveryResult::skipped('whatsapp', 'WhatsApp credentials not configured.');
        }

        // ── Determine template name ──
        $template = $templateName ?? $this->templateName;

        // ── Build payload ──
        $payload = $this->buildOtpPayload($phone, $otp, $template);

        Log::debug('MetaWhatsApp Sending Payload:', [
            'url' => "{$this->baseUrl}/{$this->phoneNumberId}/messages",
            'template_has_variables_resolved' => $this->templateHasVariables,
            'template_has_button_resolved' => (bool) config('services.whatsapp.template_has_button', true),
            'payload' => $payload,
        ]);

        // ── Dispatch HTTP Request ──
        try {
            $response = Http::withToken($this->accessToken)
                ->timeout($this->timeout)
                ->retry($this->retryTimes, $this->retrySleepMs, function (\Exception $exception, $request) {
                    // Only retry on connection/timeout errors or 5xx status codes.
                    // Do NOT retry on 4xx (bad request, auth error, rate limit).
                    if ($exception instanceof \Illuminate\Http\Client\ConnectionException) {
                        return true;
                    }

                    if ($exception instanceof \Illuminate\Http\Client\RequestException) {
                        return $exception->response->status() >= 500;
                    }

                    return false;
                })
                ->post("{$this->baseUrl}/{$this->phoneNumberId}/messages", $payload);

        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('MetaWhatsApp: RequestException caught during API call.', [
                'phone_last4' => substr($phone, -4),
                'status' => $e->response->status(),
                'body' => $e->response->json() ?? $e->response->body(),
            ]);
            return $this->handleFailedResponse($e->response, $phone);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('MetaWhatsApp: Connection failure after retries.', [
                'phone_last4' => substr($phone, -4),
                'error' => $e->getMessage(),
            ]);

            throw new OtpException(
                'Unable to reach WhatsApp service. Please try again shortly.',
                503,
                $e
            );
        } catch (\Exception $e) {
            Log::error('MetaWhatsApp: Unexpected exception during API call.', [
                'phone_last4' => substr($phone, -4),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        Log::debug('MetaWhatsApp Received Response:', [
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
        ]);

        // ── Handle error responses ──
        if ($response->failed()) {
            return $this->handleFailedResponse($response, $phone);
        }

        // ── Extract Meta Message ID on success ──
        $data = $response->json();
        $messageId = $data['messages'][0]['id'] ?? null;

        if (!$messageId) {
            Log::warning('MetaWhatsApp: Success response but no message ID found.', [
                'phone_last4' => substr($phone, -4),
                'response_keys' => array_keys($data),
            ]);
        }

        Log::info('MetaWhatsApp: OTP delivered successfully.', [
            'phone_last4' => substr($phone, -4),
            'meta_message_id' => $messageId,
        ]);

        return OtpDeliveryResult::success('whatsapp', $messageId ?? 'unknown');
    }

    /**
     * Send a raw text message via WhatsApp Cloud API.
     * Used primarily for webhook responses to user-initiated conversations.
     *
     * @param string $phone E.164 formatted phone number.
     * @param string $message The text message to send.
     * @return OtpDeliveryResult
     */
    public function sendRawMessage(string $phone, string $message): OtpDeliveryResult
    {
        if (!$this->enabled) {
            return OtpDeliveryResult::skipped('whatsapp', 'WhatsApp service is disabled.');
        }

        $recipient = ltrim($phone, '+');

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $recipient,
            'type'              => 'text',
            'text'              => [
                'preview_url' => false,
                'body'        => $message,
            ],
        ];

        try {
            $response = Http::withToken($this->accessToken)
                ->timeout($this->timeout)
                ->post("{$this->baseUrl}/{$this->phoneNumberId}/messages", $payload);

            if ($response->failed()) {
                return $this->handleFailedResponse($response, $phone);
            }

            $messageId = $response->json('messages.0.id');
            return OtpDeliveryResult::success('whatsapp', $messageId ?? 'unknown');

        } catch (\Exception $e) {
            Log::error('MetaWhatsApp: Raw message send failed.', [
                'phone_last4' => substr($phone, -4),
                'error' => $e->getMessage(),
            ]);
            throw new OtpException('Failed to send raw message via WhatsApp', 500, $e);
        }
    }

    /**
     * Build the OTP authentication template payload for Meta WhatsApp API.
     */
    private function buildOtpPayload(string $phone, string $otp, string $templateName): array
    {
        // WhatsApp API requires the phone number without the '+' prefix
        $recipient = ltrim($phone, '+');

        if ($this->sendRawText) {
            return [
                'messaging_product' => 'whatsapp',
                'recipient_type'    => 'individual',
                'to'                => $recipient,
                'type'              => 'text',
                'text'              => [
                    'preview_url' => false,
                    'body'        => "Your Executive Cricket Club verification OTP is: {$otp}. Valid for 5 minutes.",
                ],
            ];
        }

        $components = [];
        
        if ($this->templateHasVariables) {
            $parameter = [
                'type' => 'text',
                'text' => $otp,
            ];

            if ($this->templateVariableName) {
                $parameter['parameter_name'] = $this->templateVariableName;
            }

            $components[] = [
                'type' => 'body',
                'parameters' => [
                    $parameter,
                ],
            ];

            // Conditional copy/autofill button support
            if (config('services.whatsapp.template_has_button', true)) {
                $components[] = [
                    'type' => 'button',
                    'sub_type' => 'url',
                    'index' => 0,
                    'parameters' => [
                        [
                            'type' => 'text',
                            'text' => $otp,
                        ],
                    ],
                ];
            }
        }

        $templateData = [
            'name' => $templateName,
            'language' => [
                'code' => $this->templateLanguage,
            ],
        ];

        if (!empty($components)) {
            $templateData['components'] = $components;
        }

        return [
            'messaging_product' => 'whatsapp',
            'to' => $recipient,
            'type' => 'template',
            'template' => $templateData,
        ];
    }

    /**
     * Interpret a failed HTTP response from Meta and either throw or return a failure result.
     */
    private function handleFailedResponse(\Illuminate\Http\Client\Response $response, string $phone): OtpDeliveryResult
    {
        $status = $response->status();
        $body = $response->json() ?? [];
        $metaErrorCode = $body['error']['code'] ?? null;
        $metaErrorMessage = $body['error']['message'] ?? 'Unknown Meta error';

        Log::error('MetaWhatsApp: API returned error.', [
            'phone_last4' => substr($phone, -4),
            'http_status' => $status,
            'meta_error_code' => $metaErrorCode,
            'meta_error_message' => $metaErrorMessage,
            'full_response_body' => $body,
        ]);

        // ── Rate Limiting (429) ──
        if ($status === 429) {
            return OtpDeliveryResult::failure(
                provider: 'whatsapp',
                reason: 'Rate limited by Meta. Too many messages sent.',
                retryable: true,
            );
        }

        // ── Auth/Permission Errors (401, 403) ──
        if (in_array($status, [401, 403], true)) {
            throw new OtpException(
                'WhatsApp API authentication failed. Contact support.',
                $status
            );
        }

        // ── Bad Request (400) — template mismatch, invalid phone, etc. ──
        if ($status === 400) {
            throw new OtpException(
                'WhatsApp API rejected the request: ' . $metaErrorMessage,
                400
            );
        }

        // ── Server Errors (5xx) — retryable ──
        if ($status >= 500) {
            return OtpDeliveryResult::failure(
                provider: 'whatsapp',
                reason: 'Meta server error: ' . $metaErrorMessage,
                retryable: true,
            );
        }

        // ── Catch-all for any other unexpected status ──
        throw new OtpException(
            'Unexpected WhatsApp API error (HTTP ' . $status . '). Please try again later.',
            $status
        );
    }
}
