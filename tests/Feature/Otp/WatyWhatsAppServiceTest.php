<?php

namespace Tests\Feature\Otp;

use Tests\TestCase;
use App\Services\Otp\Delivery\OtpDeliveryInterface;
use App\Services\Otp\Delivery\WatyWhatsAppService;
use App\Exceptions\OtpException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WatyWhatsAppServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.waty_whatsapp.enabled', true);
        Config::set('services.waty_whatsapp.base_url', 'https://bizlawn.storesite.in/api');
        Config::set('services.waty_whatsapp.api_token', '68f1096c74os8w9rw023f8a01ee52d');
        Config::set('services.waty_whatsapp.otp_account', 'mobile_app');
        Config::set('services.waty_whatsapp.admin_phone_number', '+919911041964');
    }

    public function test_resolves_waty_whatsapp_provider_when_config_is_waty_whatsapp()
    {
        Config::set('otp.delivery_mode', 'waty_whatsapp');

        $provider = app(OtpDeliveryInterface::class);
        $this->assertInstanceOf(WatyWhatsAppService::class, $provider);
    }

    public function test_send_otp_success_returns_successful_delivery_result()
    {
        Http::fake([
            'https://bizlawn.storesite.in/api/otp/send' => Http::response([
                'success' => true,
                'message' => 'OTP sent successfully via WhatsApp.',
                'message_id' => 'wamid.HBgNMjM0NzA2MTgxNjEyMxUCABEYEjI2MDI4RjA1MzRDNEUzMkJEMAA=',
            ], 200),
        ]);

        $service = new WatyWhatsAppService();
        $result = $service->sendOtp('+919876543210', '123456');

        $this->assertTrue($result->success);
        $this->assertEquals('waty_whatsapp', $result->provider);
        $this->assertEquals('wamid.HBgNMjM0NzA2MTgxNjEyMxUCABEYEjI2MDI4RjA1MzRDNEUzMkJEMAA=', $result->providerMessageId);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://bizlawn.storesite.in/api/otp/send' &&
                $request['api_token'] === '68f1096c74os8w9rw023f8a01ee52d' &&
                $request['otp_account'] === 'mobile_app' &&
                $request['phone_number'] === '+919876543210' &&
                $request['otp_code'] === '123456' &&
                $request['admin_phone_number'] === '+919911041964';
        });
    }

    public function test_send_otp_skips_when_credentials_missing()
    {
        Config::set('services.waty_whatsapp.api_token', '');

        $service = new WatyWhatsAppService();
        $result = $service->sendOtp('+919876543210', '123456');

        $this->assertFalse($result->success);
        $this->assertStringContainsString('not configured', $result->failureReason);
    }

    public function test_send_otp_handles_401_unauthorized()
    {
        Http::fake([
            'https://bizlawn.storesite.in/api/otp/send' => Http::response([
                'success' => false,
                'message' => 'Unauthorized: Invalid or missing API token.',
            ], 401),
        ]);

        $this->expectException(OtpException::class);
        $this->expectExceptionCode(401);

        $service = new WatyWhatsAppService();
        $service->sendOtp('+919876543210', '123456');
    }

    public function test_send_otp_handles_422_unprocessable_entity()
    {
        Http::fake([
            'https://bizlawn.storesite.in/api/otp/send' => Http::response([
                'success' => false,
                'errors' => [
                    'admin_phone_number' => ['The admin phone number field is required.']
                ],
            ], 422),
        ]);

        $service = new WatyWhatsAppService();
        $result = $service->sendOtp('+919876543210', '123456');

        $this->assertFalse($result->success);
        $this->assertStringContainsString('validation failed', $result->failureReason);
    }

    public function test_send_otp_handles_500_server_error()
    {
        Http::fake([
            'https://bizlawn.storesite.in/api/otp/send' => Http::response([
                'success' => false,
                'message' => 'Internal Server Error',
            ], 500),
        ]);

        $service = new WatyWhatsAppService();
        $result = $service->sendOtp('+919876543210', '123456');

        $this->assertFalse($result->success);
        $this->assertTrue($result->retryable);
        $this->assertStringContainsString('Waty server error', $result->failureReason);
    }

    public function test_get_settings_fetches_configuration()
    {
        Http::fake([
            'https://bizlawn.storesite.in/api/otp/settings*' => Http::response([
                'success' => true,
                'otp_account' => 'mobile_app',
                'template_id' => '875800011691470',
                'otp_variable_name' => 'code',
                'template_type' => 'authentication',
                'phone_number' => '+919911041964',
                'otp_header' => 'Security Code',
                'otp_body' => 'Your verification code is {{otp}}.',
            ], 200),
        ]);

        $service = new WatyWhatsAppService();
        $settings = $service->getSettings();

        $this->assertTrue($settings['success']);
        $this->assertEquals('mobile_app', $settings['otp_account']);
        $this->assertEquals('875800011691470', $settings['template_id']);
    }
}
