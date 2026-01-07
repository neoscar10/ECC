<?php

namespace Tests\Feature;

use App\Models\User;
use App\Domain\Membership\MembershipApplication;
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
        $user = User::factory()->create();
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
                'preferred_formats' => ['test_match', 'odi'],
                'eras' => ['the_90s']
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['current_step' => 'collector_intent']);

        // 4. Collector Intent (Trigger Recommendation)
        $response = $this->withHeaders($headers)
            ->patchJson("/api/v1/membership-applications/{$application->id}/collector-intent", [
                'has_acquired_memorabilia_before' => true,
                'focus' => 'rarity',
                'investment_horizon' => '10_plus' // High score
            ]);
        
        $response->assertStatus(200)
            ->assertJsonFragment([
                'current_step' => 'payment',
                'recommended_tier_code' => 'tier2' // Expecting high tier
            ]);

        // 5. Select Tier
        // Assuming tier 1 exists from seed/migration logic or just passing ID 1 if no constraint for test now.
        // We'll skip exact ID constraint for this generic test unless seeded.
        // Let's assume we proceed to payment.

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
