<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\OtpVerification;
use App\Services\Otp\OtpService;
use App\Services\Otp\OtpGenerator;
use App\Services\Otp\Delivery\OtpDeliveryInterface;
use App\Services\Otp\PhoneNormalizer;
use Illuminate\Support\Facades\Hash;
use App\Services\Otp\OtpDeliveryResult;
use Mockery\MockInterface;

class OtpDatabaseStorageTest extends TestCase
{
    use RefreshDatabase;

    protected OtpService $otpService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock WhatsApp Service to never send actual HTTP requests during tests
        $this->mock(OtpDeliveryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendOtp')->andReturn(
                OtpDeliveryResult::success('whatsapp', 'mocked_meta_id_123')
            );
        });

        // Mock Generator to always return '112233' for verification testing
        $this->mock(OtpGenerator::class, function (MockInterface $mock) {
            $mock->shouldReceive('generate')->andReturn('112233');
        });

        $this->otpService = app(OtpService::class);
    }

    public function test_requesting_otp_creates_db_record_with_hash_and_meta_id()
    {
        $user = User::factory()->create(['phone' => '+919876543210']);

        $result = $this->otpService->requestPhoneOtp($user, $user->phone);

        $this->assertArrayHasKey('ttl_minutes', $result);
        $this->assertArrayHasKey('reference_id', $result);
        $this->assertArrayNotHasKey('_debug_otp', $result); // Ensure debug leak is closed

        $verification = OtpVerification::where('user_id', $user->id)
            ->where('purpose', 'signup')
            ->first();

        $this->assertNotNull($verification);
        $this->assertTrue(Hash::check('112233', $verification->otp_hash));
        $this->assertEquals('mocked_meta_id_123', $verification->meta_message_id);
        $this->assertNull($verification->verified_at);
        $this->assertEquals(0, $verification->attempts);
    }

    public function test_requesting_new_otp_invalidates_old_ones()
    {
        $user = User::factory()->create(['phone' => '+919876543210']);

        $this->otpService->requestPhoneOtp($user, $user->phone);
        
        $firstOtp = OtpVerification::first();
        $this->assertTrue($firstOtp->expires_at->isFuture());

        // Fast-forward cooldown by updating last_sent_at to 2 hours ago
        $firstOtp->last_sent_at = now()->subHours(2);
        $firstOtp->save();

        $this->otpService->requestPhoneOtp($user, $user->phone);

        $firstOtp->refresh();
        
        $this->assertTrue($firstOtp->isExpired());

        $activeCount = OtpVerification::pending()
            ->where('user_id', $user->id)
            ->where('purpose', 'signup')
            ->count();
            
        $this->assertEquals(1, $activeCount);
    }

    public function test_verification_succeeds_with_correct_otp()
    {
        $user = User::factory()->create(['phone' => '+919876543210']);

        $this->otpService->requestPhoneOtp($user, $user->phone);

        $isValid = $this->otpService->verifyPhoneOtp($user, $user->phone, '112233');

        $this->assertTrue($isValid);

        $verification = OtpVerification::where('user_id', $user->id)->first();
        $this->assertNotNull($verification->verified_at);
        $this->assertEquals(1, $verification->attempts);
    }

    public function test_verification_fails_with_incorrect_otp()
    {
        $user = User::factory()->create(['phone' => '+919876543210']);

        $this->otpService->requestPhoneOtp($user, $user->phone);

        try {
            $this->otpService->verifyPhoneOtp($user, $user->phone, '000000');
            $this->fail('Expected OtpException was not thrown.');
        } catch (\App\Exceptions\OtpException $e) {
            $this->assertEquals('Invalid verification code.', $e->getMessage());
        }

        $verification = OtpVerification::where('user_id', $user->id)->first();
        $this->assertNull($verification->verified_at);
        $this->assertEquals(1, $verification->attempts);
    }

    public function test_verification_fails_when_max_attempts_reached()
    {
        $user = User::factory()->create(['phone' => '+919876543210']);

        $this->otpService->requestPhoneOtp($user, $user->phone);

        $verification = OtpVerification::where('user_id', $user->id)->first();
        // Artificially max out attempts
        $verification->attempts = $verification->max_attempts;
        $verification->save();

        try {
            $this->otpService->verifyPhoneOtp($user, $user->phone, '112233');
            $this->fail('Expected OtpException was not thrown.');
        } catch (\App\Exceptions\OtpException $e) {
            $this->assertEquals('Too many incorrect attempts. Please request a new verification code.', $e->getMessage());
        }

        $verification->refresh();
        $this->assertNull($verification->verified_at);
        $this->assertEquals($verification->max_attempts, $verification->attempts);
    }

    public function test_verification_fails_when_expired()
    {
        $user = User::factory()->create(['phone' => '+919876543210']);

        $this->otpService->requestPhoneOtp($user, $user->phone);

        $verification = OtpVerification::where('user_id', $user->id)->first();
        // Artificially expire
        $verification->expires_at = now()->subMinute();
        $verification->save();

        try {
            $this->otpService->verifyPhoneOtp($user, $user->phone, '112233');
            $this->fail('Expected OtpException was not thrown.');
        } catch (\App\Exceptions\OtpException $e) {
            $this->assertEquals('The verification code has expired.', $e->getMessage());
        }
    }
}
