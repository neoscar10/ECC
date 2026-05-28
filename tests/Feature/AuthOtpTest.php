<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\OtpVerification;
use App\Services\Otp\OtpGenerator;
use App\Services\Otp\MetaWhatsAppService;
use App\Services\Otp\OtpDeliveryResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Mockery\MockInterface;
use Tests\TestCase;

class AuthOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);

        // Mock MetaWhatsAppService to avoid external API calls
        $this->mock(MetaWhatsAppService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendOtp')->andReturn(
                OtpDeliveryResult::success('whatsapp', 'mocked_meta_message_id_123')
            );
        });

        // Mock OtpGenerator to always return a static code
        $this->mock(OtpGenerator::class, function (MockInterface $mock) {
            $mock->shouldReceive('generate')->andReturn('123456');
        });

        // Clear rate limiters before each test
        RateLimiter::clear('otp_request_ip:login:' . request()->ip());
        RateLimiter::clear('otp_request_phone:login:+919876543210');
        RateLimiter::clear('otp_verify_ip:login:' . request()->ip());
        RateLimiter::clear('otp_verify_phone:login:+919876543210');
    }

    /** @test */
    public function it_can_request_otp_for_existing_user()
    {
        $user = User::factory()->create(['phone' => '+919876543210', 'phone_verified_at' => now()]);

        $response = $this->postJson('/api/v1/auth/login/otp/request', [
            'phone' => '+919876543210',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'OTP sent successfully via WhatsApp.',
            ]);

        $this->assertDatabaseHas('otp_verifications', [
            'user_id' => $user->id,
            'phone' => '+919876543210',
            'purpose' => 'login',
        ]);
    }

    /** @test */
    public function it_returns_404_when_requesting_otp_for_non_existent_user()
    {
        $response = $this->postJson('/api/v1/auth/login/otp/request', [
            'phone' => '+919999999999',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'We could not find an account with that email/phone.',
            ]);
    }

    /** @test */
    public function it_enforces_resend_cooldown_of_60_seconds()
    {
        $user = User::factory()->create(['phone' => '+919876543210', 'phone_verified_at' => now()]);

        // First request is successful
        $this->postJson('/api/v1/auth/login/otp/request', [
            'phone' => '+919876543210',
        ])->assertStatus(200);

        // Immediate second request fails with 429
        $response = $this->postJson('/api/v1/auth/login/otp/request', [
            'phone' => '+919876543210',
        ]);

        $response->assertStatus(429)
            ->assertJsonFragment([
                'success' => false,
                'message' => 'Please wait before requesting another verification code.',
            ]);
    }

    /** @test */
    public function it_can_login_with_correct_otp()
    {
        $user = User::factory()->create(['phone' => '+919876543210', 'phone_verified_at' => now()]);

        // Request OTP
        $this->postJson('/api/v1/auth/login/otp/request', [
            'phone' => '+919876543210',
        ])->assertStatus(200);

        // Verify OTP
        $response = $this->postJson('/api/v1/auth/login/otp/verify', [
            'phone' => '+919876543210',
            'otp' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                    'user',
                ]
            ]);
    }

    /** @test */
    public function it_returns_400_for_incorrect_otp()
    {
        $user = User::factory()->create(['phone' => '+919876543210', 'phone_verified_at' => now()]);

        // Request OTP
        $this->postJson('/api/v1/auth/login/otp/request', [
            'phone' => '+919876543210',
        ])->assertStatus(200);

        // Verify incorrect OTP
        $response = $this->postJson('/api/v1/auth/login/otp/verify', [
            'phone' => '+919876543210',
            'otp' => '000000',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid verification code.',
            ]);
    }

    /** @test */
    public function it_locks_out_after_max_attempts_reached()
    {
        $user = User::factory()->create(['phone' => '+919876543210', 'phone_verified_at' => now()]);

        // Request OTP
        $this->postJson('/api/v1/auth/login/otp/request', [
            'phone' => '+919876543210',
        ])->assertStatus(200);

        // Make 5 incorrect attempts
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/auth/login/otp/verify', [
                'phone' => '+919876543210',
                'otp' => '000000',
            ]);
            
            if ($i < 4) {
                $response->assertStatus(400)
                    ->assertJsonFragment(['message' => 'Invalid verification code.']);
            } else {
                $response->assertStatus(400)
                    ->assertJsonFragment(['message' => 'Too many incorrect attempts. Please request a new verification code.']);
            }
        }

        // Subsequent attempt with CORRECT code still fails due to lockout
        $response = $this->postJson('/api/v1/auth/login/otp/verify', [
            'phone' => '+919876543210',
            'otp' => '123456',
        ]);

        $response->assertStatus(400)
            ->assertJsonFragment([
                'message' => 'Too many incorrect attempts. Please request a new verification code.',
            ]);
    }

    /** @test */
    public function it_enforces_request_rate_limiting()
    {
        $user = User::factory()->create(['phone' => '+919876543210', 'phone_verified_at' => now()]);

        // Fake 10 request hits (cooldown bypassed manually in database to test rate limiting)
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/auth/login/otp/request', [
                'phone' => '+919876543210',
            ])->assertStatus(200);

            // Bypass cooldown for next loop
            $verification = OtpVerification::latest()->first();
            $verification->last_sent_at = now()->subHours(2);
            $verification->save();
        }

        // The 11th request should fail due to rate limiter
        $response = $this->postJson('/api/v1/auth/login/otp/request', [
            'phone' => '+919876543210',
        ]);

        $response->assertStatus(429)
            ->assertJsonFragment([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_validates_otp_format()
    {
        $user = User::factory()->create(['phone' => '+919876543210', 'phone_verified_at' => now()]);

        $response = $this->postJson('/api/v1/auth/login/otp/verify', [
            'phone' => '+919876543210',
            'otp' => '123', // Too short
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation Error',
            ]);
    }
}
