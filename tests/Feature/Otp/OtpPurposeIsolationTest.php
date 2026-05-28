<?php

namespace Tests\Feature\Otp;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\OtpVerification;
use App\Services\Otp\OtpService;
use App\Services\Otp\OtpGenerator;
use App\Services\Otp\MetaWhatsAppService;
use App\Services\Otp\OtpDeliveryResult;
use App\Exceptions\OtpException;
use Mockery\MockInterface;
use Illuminate\Support\Facades\RateLimiter;

class OtpPurposeIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected OtpService $otpService;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear rate limiters before each test
        RateLimiter::clear('otp_request_ip:signup:127.0.0.1');
        RateLimiter::clear('otp_request_phone:signup:+919876543210');
        RateLimiter::clear('otp_request_ip:login:127.0.0.1');
        RateLimiter::clear('otp_request_phone:login:+919876543210');
        RateLimiter::clear('otp_request_ip:password_reset:127.0.0.1');
        RateLimiter::clear('otp_request_phone:password_reset:+919876543210');
        RateLimiter::clear('otp_request_ip:phone_change:127.0.0.1');
        RateLimiter::clear('otp_request_phone:phone_change:+919876543210');

        // Mock WhatsApp Service to prevent real HTTP calls
        $this->mock(MetaWhatsAppService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendOtp')->andReturn(
                OtpDeliveryResult::success('whatsapp', 'mocked_meta_message_id')
            );
        });

        // Mock OTP Generator to always return a predictable code
        $this->mock(OtpGenerator::class, function (MockInterface $mock) {
            $mock->shouldReceive('generate')->andReturn('123456');
        });

        $this->otpService = app(OtpService::class);
    }

    /**
     * Test that an OTP generated for one purpose cannot be verified for another.
     */
    public function test_otp_purpose_isolation()
    {
        // 1. Generate a signup OTP
        $phone = '+919876543210';
        $this->otpService->requestRegistrationOtp($phone);

        // 2. Try to verify it under login purpose
        $user = User::factory()->create(['phone' => $phone, 'phone_verified_at' => now()]);
        try {
            $this->otpService->verifyLoginOtp($user, $phone, '123456');
            $this->fail('Expected OtpException for cross-purpose verification.');
        } catch (OtpException $e) {
            $this->assertEquals('Invalid verification code.', $e->getMessage());
        }

        // 3. Try to verify it under password reset purpose
        try {
            $this->otpService->verifyPasswordResetOtp($user, $phone, '123456');
            $this->fail('Expected OtpException for cross-purpose verification.');
        } catch (OtpException $e) {
            $this->assertEquals('Invalid verification code.', $e->getMessage());
        }

        // Delete the user so that signup verification check does not think it's already registered
        $user->delete();

        // 4. Try to verify it under signup purpose
        $isValid = $this->otpService->verifyPhoneOtp(new User(), $phone, '123456');
        $this->assertTrue($isValid);
    }

    /**
     * Test validation rules for the signup purpose.
     */
    public function test_signup_purpose_validation_rules()
    {
        $phone = '+919876543210';
        
        // Create user with already verified phone
        User::factory()->create(['phone' => $phone, 'phone_verified_at' => now()]);

        // Request signup OTP must fail
        try {
            $this->otpService->requestRegistrationOtp($phone);
            $this->fail('Expected OtpException when requesting signup OTP for registered phone.');
        } catch (OtpException $e) {
            $this->assertEquals('This phone number is already registered.', $e->getMessage());
            $this->assertEquals(422, $e->getCode());
        }

        // Verify signup OTP must also fail the validation policies
        try {
            $this->otpService->verifyPhoneOtp(new User(), $phone, '123456');
            $this->fail('Expected OtpException when verifying signup OTP for registered phone.');
        } catch (OtpException $e) {
            $this->assertEquals('This phone number is already registered.', $e->getMessage());
            $this->assertEquals(422, $e->getCode());
        }
    }

    /**
     * Test validation rules for the login purpose.
     */
    public function test_login_purpose_validation_rules()
    {
        $phone = '+919876543210';

        // 1. Non-existent user
        try {
            $this->otpService->requestLoginOtp(new User(), $phone);
            $this->fail('Expected OtpException for login request on non-existent user.');
        } catch (OtpException $e) {
            $this->assertEquals('We could not find an account with that email/phone.', $e->getMessage());
            $this->assertEquals(404, $e->getCode());
        }

        // 2. Unverified user
        $user = User::factory()->create(['phone' => $phone, 'phone_verified_at' => null]);
        try {
            $this->otpService->requestLoginOtp($user, $phone);
            $this->fail('Expected OtpException for login request on unverified user.');
        } catch (OtpException $e) {
            $this->assertEquals('Please verify your phone number before logging in.', $e->getMessage());
            $this->assertEquals(400, $e->getCode());
        }

        // 3. Suspended user
        $user->update(['phone_verified_at' => now(), 'is_suspended' => true]);
        try {
            $this->otpService->requestLoginOtp($user, $phone);
            $this->fail('Expected OtpException for login request on suspended user.');
        } catch (OtpException $e) {
            $this->assertStringContainsString('Your account has been suspended.', $e->getMessage());
            $this->assertEquals(403, $e->getCode());
        }

        // Verify login OTP must also fail for suspended user
        try {
            $this->otpService->verifyLoginOtp($user, $phone, '123456');
            $this->fail('Expected OtpException when verifying login OTP for suspended user.');
        } catch (OtpException $e) {
            $this->assertStringContainsString('Your account has been suspended.', $e->getMessage());
            $this->assertEquals(403, $e->getCode());
        }
    }

    /**
     * Test validation rules for the password reset purpose.
     */
    public function test_password_reset_purpose_validation_rules()
    {
        $phone = '+919876543210';

        // Non-existent user
        try {
            $this->otpService->requestPasswordResetOtp(new User(), $phone);
            $this->fail('Expected OtpException for password reset request on non-existent user.');
        } catch (OtpException $e) {
            $this->assertEquals('We could not find an account with that email/phone.', $e->getMessage());
            $this->assertEquals(404, $e->getCode());
        }
    }

    /**
     * Test validation rules for the phone change purpose.
     */
    public function test_phone_change_purpose_validation_rules()
    {
        $phone = '+919876543210';
        $otherPhone = '+919876543211';

        $userA = User::factory()->create(['phone' => $phone, 'phone_verified_at' => now()]);
        $userB = User::factory()->create(['phone' => $otherPhone, 'phone_verified_at' => now()]);

        // 1. Requesting phone change with no authenticated user
        try {
            $this->otpService->requestPhoneChangeOtp(new User(), $otherPhone);
            $this->fail('Expected OtpException for anonymous phone change request.');
        } catch (OtpException $e) {
            $this->assertEquals('Unauthorized. Please log in first.', $e->getMessage());
            $this->assertEquals(401, $e->getCode());
        }

        // 2. Requesting phone change to a number already in use
        try {
            $this->otpService->requestPhoneChangeOtp($userA, $otherPhone);
            $this->fail('Expected OtpException for phone change to number already in use.');
        } catch (OtpException $e) {
            $this->assertEquals('This phone number is already in use.', $e->getMessage());
            $this->assertEquals(422, $e->getCode());
        }

        // 3. Verifying phone change to a number already in use
        try {
            $this->otpService->verifyPhoneChangeOtp($userA, $otherPhone, '123456');
            $this->fail('Expected OtpException when verifying phone change to number already in use.');
        } catch (OtpException $e) {
            $this->assertEquals('This phone number is already in use.', $e->getMessage());
            $this->assertEquals(422, $e->getCode());
        }
    }

    /**
     * Test independent rate limiting per purpose.
     */
    public function test_independent_rate_limiting_per_purpose()
    {
        $phone = '+919876543210';
        $user = User::factory()->create(['phone' => $phone, 'phone_verified_at' => now()]);

        // Max out rate limit for PASSWORD_RESET (3 attempts)
        for ($i = 0; $i < 3; $i++) {
            $this->otpService->requestPasswordResetOtp($user, $phone);
            // Move time/cooldown forward if needed, or update DB record directly to bypass cooldown
            $last = OtpVerification::latest()->first();
            $last->update(['last_sent_at' => now()->subMinutes(5)]);
        }

        // 4th password reset attempt must fail
        try {
            $this->otpService->requestPasswordResetOtp($user, $phone);
            $this->fail('Expected OtpException due to password reset rate limiting.');
        } catch (OtpException $e) {
            $this->assertStringContainsString('Too many verification code requests', $e->getMessage());
            $this->assertEquals(429, $e->getCode());
        }

        // Login OTP request should STILL be allowed because limits are independent
        $loginResult = $this->otpService->requestLoginOtp($user, $phone);
        $this->assertTrue($loginResult['delivered']);
    }

    /**
     * Test independent resend cooldowns per purpose.
     */
    public function test_independent_resend_cooldowns_per_purpose()
    {
        $phone = '+919876543210';
        $user = User::factory()->create(['phone' => $phone, 'phone_verified_at' => now()]);

        // Request login OTP
        $this->otpService->requestLoginOtp($user, $phone);

        // Immediate subsequent request for login must fail cooldown
        try {
            $this->otpService->requestLoginOtp($user, $phone);
            $this->fail('Expected OtpException due to login resend cooldown.');
        } catch (OtpException $e) {
            $this->assertStringContainsString('Please wait before requesting another verification code.', $e->getMessage());
            $this->assertEquals(429, $e->getCode());
        }

        // Request password reset OTP immediately (should succeed since cooldowns are separate)
        $resetResult = $this->otpService->requestPasswordResetOtp($user, $phone);
        $this->assertTrue($resetResult['delivered']);
    }
}
