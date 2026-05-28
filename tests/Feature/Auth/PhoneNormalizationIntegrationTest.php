<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Services\Otp\PhoneNormalizer;

class PhoneNormalizationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed roles for testing
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_api_registration_normalizes_phone_and_enforces_uniqueness()
    {
        // 1. Register with Nigeria local format
        $response1 = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Nigeria',
            'email' => 'john.ng@example.com',
            'phone' => '08012345678',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        if ($response1->status() !== 200) {
            $response1->dump();
        }

        $response1->assertStatus(200);
        $this->assertDatabaseHas('pending_registrations', [
            'email' => 'john.ng@example.com',
            'phone' => '+2348012345678', // Assert E.164 normalization
        ]);

        // 2. Register again with equivalent international format without '+'
        $response2 = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Duplicate',
            'email' => 'john.dup@example.com',
            'phone' => '2348012345678',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response2->assertStatus(422);
        $response2->assertJsonValidationErrors('phone');
    }

    public function test_api_registration_rejects_invalid_phone_number()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Bad Phone',
            'email' => 'bad.phone@example.com',
            'phone' => '12345abc', // Invalid characters + too short
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('phone');
    }

    // ── Authentication Login Tests ──

    public function test_api_login_resolves_different_phone_formats()
    {
        $user = User::factory()->create([
            'phone' => '+919876543210',
            'password' => Hash::make('password123'),
        ]);

        // Login using local Indian format (10 digits)
        $response1 = $this->postJson('/api/v1/auth/login', [
            'phone' => '9876543210',
            'password' => 'password123',
        ]);

        $response1->assertStatus(200);
        $response1->assertJsonStructure(['data' => ['access_token']]);

        // Login using international Indian format without plus
        $response2 = $this->postJson('/api/v1/auth/login', [
            'phone' => '919876543210',
            'password' => 'password123',
        ]);

        $response2->assertStatus(200);
    }

    public function test_api_login_fails_gracefully_with_malformed_phone()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => 'not_a_phone_number',
            'password' => 'password123',
        ]);

        // Should fail gracefully with 404 (user not found) instead of 500
        $response->assertStatus(404);
        $response->assertJsonFragment([
            'message' => 'We could not find an account with that email/phone.'
        ]);
    }

    // ── Profile Update Tests ──

    public function test_profile_update_normalizes_phone()
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->patchJson('/api/v1/profile', [
            'phone' => '08012345678', // Nigeria local format
        ], [
            'Authorization' => "Bearer {$token}"
        ]);

        if ($response->status() !== 200) {
            $response->dump();
        }

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'phone' => '+2348012345678', // Normalized
        ]);
    }

    // ── Existing Data Migration Tests ──

    public function test_migration_normalizes_existing_users()
    {
        // Disable model events if any, or create using query builder to bypass any hooks
        $u1 = User::factory()->create(['phone' => '08012345678']); // Nigeria
        $u2 = User::factory()->create(['phone' => '9876543210']);  // India
        $u3 = User::factory()->create(['phone' => '123abc45']);    // Invalid
        $u4 = User::factory()->create(['phone' => '+2348022222222']); // Already valid, distinct E.164
        $u5 = User::factory()->create(['phone' => '2348012345678']); // Nigeria duplicate of u1

        // Execute migration class directly
        $migration = require database_path('migrations/2026_05_25_232347_normalize_existing_user_phones.php');
        $migration->up();

        $u1->refresh();
        $u2->refresh();
        $u3->refresh();
        $u4->refresh();
        $u5->refresh();

        // One of the Nigerian duplicates is normalized to +2348012345678, the other gets suffixed with _dup_
        if (str_starts_with($u1->phone, '08012345678_dup_')) {
            $this->assertEquals('+2348012345678', $u5->phone);
        } else {
            $this->assertEquals('+2348012345678', $u1->phone);
            $this->assertTrue(str_starts_with($u5->phone, '2348012345678_dup_'));
        }

        // India number normalized
        $this->assertEquals('+919876543210', $u2->phone);

        // Invalid number flagged
        $this->assertEquals('123abc45_invalid', $u3->phone);

        // Already normalized stays same
        $this->assertEquals('+2348022222222', $u4->phone);
    }
}
