<?php

namespace Tests\Feature\Otp;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Services\Otp\OtpService;

class WhatsApp24HourWindowFallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['otp.delivery_mode' => 'meta_whatsapp']);
        config(['services.whatsapp.enabled' => true]);
        config(['services.whatsapp.phone_number_id' => 'test_phone_id']);
        config(['services.whatsapp.access_token' => 'test_token']);
        config(['services.whatsapp.send_raw_text' => true]);
    }

    public function test_when_meta_returns_24_hour_window_error_otp_service_falls_back_to_direct_message_mode()
    {
        // Fake Meta API response returning 24h window closed error (Code 131047)
        Http::fake([
            'https://graph.facebook.com/v22.0/test_phone_id/messages' => Http::response([
                'error' => [
                    'message' => '(#131047) Re-engagement message: Message failed to send because more than 24 hours have passed since the user last sent a message to this phone number.',
                    'type' => 'OAuthException',
                    'code' => 131047,
                    'error_data' => [
                        'messaging_product' => 'whatsapp',
                        'details' => 'Re-engagement message'
                    ]
                ]
            ], 400),
        ]);

        $phone = '+919876543210';
        $otpService = app(OtpService::class);

        // Act: Request registration OTP
        $result = $otpService->requestRegistrationOtp($phone);

        // Assert: It should NOT throw exception, but gracefully fall back to direct_message mode
        $this->assertEquals('direct_message', $result['otp_method']);
        $this->assertFalse($result['delivered']);
        $this->assertStringContainsString('Please send a message to our WhatsApp number', $result['message']);

        // Assert: Plaintext OTP was cached for incoming webhook resolution
        $cachedOtp = Cache::get('otp_plaintext_' . $phone);
        $this->assertNotNull($cachedOtp);
        $this->assertEquals(6, strlen($cachedOtp));
    }
}
