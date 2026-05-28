<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use App\Models\OtpVerification;
use App\Services\Otp\Delivery\OtpDeliveryInterface;
use App\Services\Otp\Delivery\DevOtpDeliveryService;
use App\Services\Otp\Delivery\MetaWhatsAppService;
use App\Services\Otp\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

class DevOtpModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_resolves_dev_provider_when_config_is_dev()
    {
        Config::set('otp.delivery_mode', 'dev');

        $provider = app(OtpDeliveryInterface::class);
        $this->assertInstanceOf(DevOtpDeliveryService::class, $provider);
    }

    public function test_resolves_meta_provider_when_config_is_meta_whatsapp()
    {
        Config::set('otp.delivery_mode', 'meta_whatsapp');

        $provider = app(OtpDeliveryInterface::class);
        $this->assertInstanceOf(MetaWhatsAppService::class, $provider);
    }

    public function test_refuses_startup_in_production_env_when_mode_is_dev()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CRITICAL: DEV OTP mode cannot be enabled in production environments.');

        // Set config to dev and environment to production
        Config::set('otp.delivery_mode', 'dev');
        $this->app['env'] = 'production';

        // Resolving the interface triggers the register callback check
        app()->make(OtpDeliveryInterface::class);
    }

    public function test_exposes_dev_otp_in_local_debug_mode()
    {
        $this->app['env'] = 'local';
        Config::set('app.debug', true);
        Config::set('otp.delivery_mode', 'dev');

        $user = User::factory()->create(['phone' => '+919876543210']);
        $otpService = app(OtpService::class);

        $result = $otpService->requestPhoneOtp($user, $user->phone);

        $this->assertArrayHasKey('dev_otp', $result);
        $this->assertEquals(6, strlen($result['dev_otp']));
    }

    public function test_does_not_expose_dev_otp_when_debug_disabled()
    {
        $this->app['env'] = 'local';
        Config::set('app.debug', false);
        Config::set('otp.delivery_mode', 'dev');

        $user = User::factory()->create(['phone' => '+919876543210']);
        $otpService = app(OtpService::class);

        $result = $otpService->requestPhoneOtp($user, $user->phone);

        $this->assertArrayNotHasKey('dev_otp', $result);
    }

    public function test_does_not_expose_dev_otp_when_env_is_not_local()
    {
        $this->app['env'] = 'testing';
        Config::set('app.debug', true);
        Config::set('otp.delivery_mode', 'dev');

        $user = User::factory()->create(['phone' => '+919876543210']);
        $otpService = app(OtpService::class);

        $result = $otpService->requestPhoneOtp($user, $user->phone);

        $this->assertArrayNotHasKey('dev_otp', $result);
    }
}
