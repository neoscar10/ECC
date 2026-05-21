<?php

namespace Tests\Feature\Membership;

use App\Models\User;
use App\Models\MembershipApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalDetailsSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure roles exist if needed, though basic auth might be enough
        $this->seed(\Database\Seeders\RoleSeeder::class); 
    }

    public function test_updating_personal_details_syncs_to_user_model()
    {
        // 1. Create User with null fields
        $user = User::factory()->create([
            'full_name' => null,
            'date_of_birth' => null,
            'country' => null,
            'city' => null,
            'phone_verified_at' => now(), // Bypass verified_phone middleware
        ]);
        
        $token = auth('api')->login($user);

        // 2. Create Application
        $application = MembershipApplication::create([
            'user_id' => $user->id,
            'status' => 'draft',
            'current_step' => 'personal_details'
        ]);

        // 3. Payload
        $payload = [
            'full_name' => 'Synced Name',
            'date_of_birth' => '1990-01-01',
            'country' => 'India',
            'city' => 'Mumbai'
        ];

        // 4. Request
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->patchJson("/api/v1/membership-applications/{$application->id}/personal-details", $payload);

        // 5. Assertions
        $response->assertStatus(200);

        // Check Application
        $this->assertDatabaseHas('membership_applications', [
            'id' => $application->id,
            'current_step' => 'cricket_profile'
        ]);
        
        // Check User Model (The Core Requirement)
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'full_name' => 'Synced Name',
            'date_of_birth' => '1990-01-01',
            'country' => 'India',
            'city' => 'Mumbai'
        ]);
    }

    public function test_user_cannot_update_others_application()
    {
        $owner = User::factory()->create();
        $application = MembershipApplication::create([
            'user_id' => $owner->id,
            'status' => 'draft'
        ]);

        $attacker = User::factory()->create([
            'phone_verified_at' => now(),
        ]);
        $token = auth('api')->login($attacker);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->patchJson("/api/v1/membership-applications/{$application->id}/personal-details", [
                'full_name' => 'Hacker',
                'date_of_birth' => '1990-01-01',
                'country' => 'X',
                'city' => 'Y'
            ]);

        $response->assertStatus(404); // Controller currently doing ModelNotFound via checks? Or 403? 
        // Logic says: getApplicationOr404 checks user_id matches
    }
}
