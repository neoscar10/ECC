<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PendingRegistration;
use App\Models\OtpVerification;
use App\Models\MembershipApplication;
use App\Services\Otp\MetaWhatsAppService;
use App\Services\Otp\OtpGenerator;
use App\Services\Otp\OtpDeliveryResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery\MockInterface;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed roles for testing
        $this->seed(\Database\Seeders\RoleSeeder::class);

        // Mock MetaWhatsAppService to avoid external API calls
        $this->mock(MetaWhatsAppService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendOtp')->andReturn(
                OtpDeliveryResult::success('whatsapp', 'mocked_meta_id_auth_test')
            );
        });

        // Mock OtpGenerator to return a static code
        $this->mock(OtpGenerator::class, function (MockInterface $mock) {
            $mock->shouldReceive('generate')->andReturn('123456');
        });
    }

    public function test_registration_does_not_create_user_immediately()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Pending User',
            'email' => 'pending@example.com',
            'phone' => '+919876543210',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                    'user',
                    'application'
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => 0,
                        'name' => 'Pending User',
                        'email' => 'pending@example.com',
                        'phone' => '+919876543210',
                        'phone_verified_at' => null,
                    ],
                    'application' => null,
                ]
            ]);

        // Assert user does NOT exist in users table
        $this->assertDatabaseMissing('users', ['email' => 'pending@example.com']);

        // Assert record exists in pending_registrations table
        $this->assertDatabaseHas('pending_registrations', [
            'email' => 'pending@example.com',
            'phone' => '+919876543210'
        ]);
    }

    public function test_verification_completes_registration_and_creates_user()
    {
        // 1. Initiate registration
        $registerResponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'Verified User',
            'email' => 'verified@example.com',
            'phone' => '+919876543210',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $tempToken = $registerResponse->json('data.access_token');
        $this->assertNotNull($tempToken);

        // 2. Verify OTP
        $verifyResponse = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '+919876543210',
            'otp' => '123456',
        ], [
            'Authorization' => 'Bearer ' . $tempToken
        ]);

        if ($verifyResponse->status() !== 200) {
            $verifyResponse->dump();
        }

        $verifyResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                    'user' => ['id', 'name', 'email', 'phone_verified_at'],
                    'application' => ['id', 'status', 'current_step']
                ]
            ]);

        // 3. Assert DB state
        $this->assertDatabaseHas('users', [
            'email' => 'verified@example.com',
            'phone' => '+919876543210'
        ]);

        $user = User::where('email', 'verified@example.com')->first();
        $this->assertNotNull($user->phone_verified_at);
        $this->assertTrue($user->hasRole('user'));

        // Assert pending_registration is deleted
        $this->assertDatabaseMissing('pending_registrations', [
            'email' => 'verified@example.com'
        ]);

        // Assert membership application is created
        $this->assertDatabaseHas('membership_applications', [
            'user_id' => $user->id,
            'status' => 'draft'
        ]);
    }

    public function test_failed_verification_leaves_no_user()
    {
        $registerResponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'Failed User',
            'email' => 'failed@example.com',
            'phone' => '+919876543210',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $tempToken = $registerResponse->json('data.access_token');

        // Verify incorrect OTP
        $verifyResponse = $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => '+919876543210',
            'otp' => '000000',
        ], [
            'Authorization' => 'Bearer ' . $tempToken
        ]);

        $verifyResponse->assertStatus(400);

        // Assert no user created
        $this->assertDatabaseMissing('users', ['email' => 'failed@example.com']);
        $this->assertDatabaseHas('pending_registrations', ['email' => 'failed@example.com']);
    }

    public function test_uniqueness_checks_across_both_tables()
    {
        // Register first pending user
        $this->postJson('/api/v1/auth/register', [
            'name' => 'User One',
            'email' => 'one@example.com',
            'phone' => '+919876543210',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(200);

        // Try duplicate email registration (pending conflict)
        $this->postJson('/api/v1/auth/register', [
            'name' => 'User Two',
            'email' => 'one@example.com',
            'phone' => '+918765432109',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)
          ->assertJsonValidationErrors('email');

        // Try duplicate phone registration (pending conflict)
        $this->postJson('/api/v1/auth/register', [
            'name' => 'User Two',
            'email' => 'two@example.com',
            'phone' => '+919876543210',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)
          ->assertJsonValidationErrors('phone');

        // Promote first pending user to permanent user
        $pending = PendingRegistration::first();
        $this->postJson('/api/v1/auth/verify-otp', [
            'phone' => $pending->phone,
            'otp' => '123456'
        ], [
            'Authorization' => 'Bearer ' . auth('api')->tokenById($pending->id) // bypass mock JWT token generation simplicity
        ]);

        // Try duplicate email registration (permanent conflict)
        $this->postJson('/api/v1/auth/register', [
            'name' => 'User Three',
            'email' => 'one@example.com',
            'phone' => '+918765432109',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)
          ->assertJsonValidationErrors('email');
    }

    public function test_resend_otp_pending_registration()
    {
        $registerResponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'Resend User',
            'email' => 'resend@example.com',
            'phone' => '+919876543210',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $tempToken = $registerResponse->json('data.access_token');

        // Fast-forward cooldown in database for testing resend
        $otp = OtpVerification::latest()->first();
        $otp->last_sent_at = now()->subSeconds(65);
        $otp->save();

        // Request Resend OTP
        $resendResponse = $this->postJson('/api/v1/auth/request-otp', [
            'phone' => '+919876543210',
        ], [
            'Authorization' => 'Bearer ' . $tempToken
        ]);

        $resendResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'OTP sent successfully.'
            ]);
    }

    public function test_cleanup_expired_pending_registrations()
    {
        // Create an expired pending registration record
        $expired = PendingRegistration::create([
            'name' => 'Expired User',
            'email' => 'expired@example.com',
            'phone' => '+919876543210',
            'password_hash' => Hash::make('password123'),
            'expires_at' => now()->subMinutes(5),
        ]);

        // Initiate a new registration (which runs dynamic cleanup)
        $this->postJson('/api/v1/auth/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'phone' => '+918765432109',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(200);

        // Assert expired record is deleted
        $this->assertDatabaseMissing('pending_registrations', [
            'id' => $expired->id
        ]);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'password',
        ]);
        $user->assignRole('user');

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'access_token',
                ]
            ]);
    }

    public function test_user_can_get_me()
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonFragment(['email' => $user->email]);
    }
}
