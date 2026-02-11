<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthOtpTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_request_otp_for_existing_user()
    {
        $user = User::factory()->create(['phone' => '+1234567890']);

        $response = $this->postJson('/api/v1/auth/login/otp/request', [
            'phone' => '+1234567890',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'OTP requested. Use any 6-digit code to continue.',
            ]);
    }

    /** @test */
    public function it_returns_404_when_requesting_otp_for_non_existent_user()
    {
        $response = $this->postJson('/api/v1/auth/login/otp/request', [
            'phone' => '+9999999999',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'We could not find an account with that email/phone.',
            ]);
    }

    /** @test */
    public function it_can_login_with_any_6_digit_otp_for_existing_user()
    {
        $user = User::factory()->create(['phone' => '+1234567890']);

        // Try with 123456
        $response = $this->postJson('/api/v1/auth/login/otp/verify', [
            'phone' => '+1234567890',
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

        // Try with 654321
        $response2 = $this->postJson('/api/v1/auth/login/otp/verify', [
            'phone' => '+1234567890',
            'otp' => '654321',
        ]);
        
        $response2->assertStatus(200);
    }

    /** @test */
    public function it_returns_404_when_verifying_otp_for_non_existent_user()
    {
        $response = $this->postJson('/api/v1/auth/login/otp/verify', [
            'phone' => '+9999999999',
            'otp' => '123456',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'We could not find an account with that email/phone.',
            ]);
    }

    /** @test */
    public function it_validates_otp_format()
    {
        $user = User::factory()->create(['phone' => '+1234567890']);

        $response = $this->postJson('/api/v1/auth/login/otp/verify', [
            'phone' => '+1234567890',
            'otp' => '123', // Too short
        ]);

        $response->assertStatus(422)
             ->assertJson([
                 'success' => false,
                 'message' => 'Validation Error',
             ]);
    }
}
