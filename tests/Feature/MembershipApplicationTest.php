<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\MembershipApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class MembershipApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\DatabaseSeeder::class); // To ensure admin exists
    }

    public function test_registration_creates_draft_application()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'New Applicant',
            'email' => 'applicant@example.com',
            'phone' => '+447700900000',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user',
                    'access_token',
                    'application' => ['id', 'status', 'current_step']
                ]
            ]);

        $this->assertDatabaseHas('membership_applications', [
            'status' => 'draft',
            'current_step' => 'personal_details'
        ]);
    }

    public function test_full_application_flow()
    {
        // 1. Register
        $user = User::factory()->create(['phone_verified_at' => now(), 'phone' => '+447700900000']);
        $user->assignRole('user');
        $token = auth('api')->login($user);

        $application = MembershipApplication::create([
            'user_id' => $user->id,
            'status' => 'draft',
            'current_step' => 'personal_details'
        ]);

        $headers = ['Authorization' => 'Bearer ' . $token];

        // 2. Personal Details
        $response = $this->withHeaders($headers)
            ->patchJson("/api/v1/membership-applications/{$application->id}/personal-details", [
                'full_name' => 'John Cricketer',
                'date_of_birth' => '1990-05-15',
                'country' => 'United Kingdom',
                'city' => 'London'
            ]);
        
        $response->assertStatus(200)
            ->assertJsonFragment(['current_step' => 'cricket_profile']);

        // 3. Cricket Profile
        $response = $this->withHeaders($headers)
            ->patchJson("/api/v1/membership-applications/{$application->id}/cricket-profile", [
                'preferred_formats' => ['TEST', 'ODI'],
                'eras' => ['ODI_90S_ERA']
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['current_step' => 'collector_intent']);

        // 4. Collector Intent (Trigger Recommendation)
        $response = $this->withHeaders($headers)
            ->patchJson("/api/v1/membership-applications/{$application->id}/collector-intent", [
                'has_acquired_memorabilia_before' => true,
                'focus' => 'RARITY',
                'investment_horizon' => 'Y10_PLUS' // High score
            ]);
        
        $response->assertStatus(200)
            ->assertJsonFragment([
                'current_step' => 'tier_selection',
                'recommended_tier_code' => 'sovereign' // Sovereign is seeded high tier
            ]);

        // 5. Select Tier
        $response = $this->withHeaders($headers)
            ->postJson("/api/v1/membership-applications/{$application->id}/select-tier", [
                'tier_id' => 3 // Select Gold tier (non-free)
            ]);
        
        $response->assertStatus(200)
            ->assertJsonFragment([
                'current_step' => 'payment',
                'selected_tier_id' => 3
            ]);

        // 6. Payment (Test)
        $response = $this->withHeaders($headers)
            ->postJson("/api/v1/membership-applications/{$application->id}/payment/confirm", [
                'method' => 'card',
                'amount' => 5000,
                'currency' => 'USD',
                'cardholder_name' => 'John Doe',
                'last4' => '4242',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['payment_status' => 'test_paid']);

        // 7. Security Check: Raw Card Data
        $response = $this->withHeaders($headers)
            ->postJson("/api/v1/membership-applications/{$application->id}/payment/confirm", [
                'method' => 'card',
                'amount' => 5000,
                'cardholder_name' => 'John Doe',
                'last4' => '4242',
                'card_number' => '424242424242', // Forbidden
            ]);
        
        $response->assertStatus(400);

        // 8. Submit
        $response = $this->withHeaders($headers)
            ->postJson("/api/v1/membership-applications/{$application->id}/submit");

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'submitted']);
    }
}
