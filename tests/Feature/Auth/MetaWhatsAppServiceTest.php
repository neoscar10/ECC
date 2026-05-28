<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use App\Services\Otp\Delivery\MetaWhatsAppService;
use App\Services\Otp\OtpDeliveryResult;

class MetaWhatsAppServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure WhatsApp is enabled and configured for these tests
        config([
            'services.whatsapp.enabled' => true,
            'services.whatsapp.access_token' => 'test_token_abc123',
            'services.whatsapp.phone_number_id' => '1234567890',
            'services.whatsapp.template_name' => 'authentication',
            'services.whatsapp.api_version' => 'v22.0',
            'services.whatsapp.timeout' => 5,
            'services.whatsapp.retry_times' => 1,
            'services.whatsapp.retry_sleep_ms' => 10,
            'services.whatsapp.template_has_button' => true,
        ]);
    }

    private function makeService(): MetaWhatsAppService
    {
        return new MetaWhatsAppService();
    }

    // ── Success Scenarios ──

    public function test_successful_otp_delivery_returns_success_result()
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => '919876543210', 'wa_id' => '919876543210']],
                'messages' => [['id' => 'wamid.HBgNOTE5ODc2NTQzMjEwFQIAERgSMkY3']],
            ], 200),
        ]);

        $service = $this->makeService();
        $result = $service->sendOtp('+919876543210', '654321');

        $this->assertInstanceOf(OtpDeliveryResult::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals('whatsapp', $result->provider);
        $this->assertEquals('wamid.HBgNOTE5ODc2NTQzMjEwFQIAERgSMkY3', $result->providerMessageId);
        $this->assertNull($result->failureReason);
        $this->assertFalse($result->retryable);

        Http::assertSentCount(1);
    }

    public function test_sends_correct_payload_to_meta_api()
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.test123']],
            ], 200),
        ]);

        $service = $this->makeService();
        $service->sendOtp('+919876543210', '789012');

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->url() === 'https://graph.facebook.com/v22.0/1234567890/messages'
                && $request->hasHeader('Authorization', 'Bearer test_token_abc123')
                && $body['messaging_product'] === 'whatsapp'
                && $body['to'] === '919876543210'  // No '+' prefix
                && $body['type'] === 'template'
                && $body['template']['name'] === 'authentication'
                && $body['template']['components'][0]['type'] === 'body'
                && $body['template']['components'][0]['parameters'][0]['text'] === '789012'
                && $body['template']['components'][1]['type'] === 'button';
        });
    }

    // ── Disabled / Unconfigured Scenarios ──

    public function test_returns_skipped_when_whatsapp_is_disabled()
    {
        config(['services.whatsapp.enabled' => false]);

        Http::fake(); // Should never be called

        $service = $this->makeService();
        $result = $service->sendOtp('+919876543210', '123456');

        $this->assertInstanceOf(OtpDeliveryResult::class, $result);
        $this->assertFalse($result->success);
        $this->assertEquals('whatsapp', $result->provider);
        $this->assertStringContainsString('disabled', $result->failureReason);

        Http::assertNothingSent();
    }

    public function test_returns_skipped_when_credentials_are_missing()
    {
        config([
            'services.whatsapp.enabled' => true,
            'services.whatsapp.access_token' => '',
            'services.whatsapp.phone_number_id' => '',
        ]);

        Http::fake(); // Should never be called

        $service = $this->makeService();
        $result = $service->sendOtp('+919876543210', '123456');

        $this->assertFalse($result->success);
        $this->assertStringContainsString('not configured', $result->failureReason);

        Http::assertNothingSent();
    }

    // ── Error Response Scenarios ──

    public function test_rate_limiting_returns_retryable_failure()
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'code' => 80007,
                    'message' => 'Rate limit hit',
                ],
            ], 429),
        ]);

        $service = $this->makeService();
        $result = $service->sendOtp('+919876543210', '123456');

        $this->assertFalse($result->success);
        $this->assertTrue($result->retryable);
        $this->assertStringContainsString('Rate limited', $result->failureReason);
    }

    public function test_auth_error_throws_otp_exception()
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'code' => 190,
                    'message' => 'Invalid OAuth access token',
                ],
            ], 401),
        ]);

        $this->expectException(\App\Exceptions\OtpException::class);
        $this->expectExceptionMessage('authentication failed');

        $service = $this->makeService();
        $service->sendOtp('+919876543210', '123456');
    }

    public function test_permission_error_throws_otp_exception()
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'code' => 10,
                    'message' => 'Permission denied',
                ],
            ], 403),
        ]);

        $this->expectException(\App\Exceptions\OtpException::class);
        $this->expectExceptionMessage('authentication failed');

        $service = $this->makeService();
        $service->sendOtp('+919876543210', '123456');
    }

    public function test_bad_request_throws_otp_exception_with_meta_message()
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'code' => 100,
                    'message' => 'Invalid parameter: template name does not exist',
                ],
            ], 400),
        ]);

        $this->expectException(\App\Exceptions\OtpException::class);
        $this->expectExceptionMessage('Invalid parameter: template name does not exist');

        $service = $this->makeService();
        $service->sendOtp('+919876543210', '123456');
    }

    public function test_server_error_returns_retryable_failure()
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'code' => 2,
                    'message' => 'Internal server error',
                ],
            ], 500),
        ]);

        $service = $this->makeService();
        $result = $service->sendOtp('+919876543210', '123456');

        $this->assertFalse($result->success);
        $this->assertTrue($result->retryable);
        $this->assertStringContainsString('server error', $result->failureReason);
    }

    // ── Connection Failure Scenarios ──

    public function test_connection_timeout_throws_otp_exception()
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
        });

        $this->expectException(\App\Exceptions\OtpException::class);
        $this->expectExceptionMessage('Unable to reach WhatsApp service');

        $service = $this->makeService();
        $service->sendOtp('+919876543210', '123456');
    }
}
