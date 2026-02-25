<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\MembershipTier;
use App\Models\MembershipApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipStatusApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_membership_status_for_authenticated_user()
    {
        $user = User::factory()->create();
        $tier = MembershipTier::factory()->create(['name' => 'Gold']);
        
        // Create an application
        MembershipApplication::create([
            'user_id' => $user->id,
            'selected_tier_id' => $tier->id,
            'status' => 'draft',
            'current_step' => 'personal_details'
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/membership/status');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'has_active_membership',
                    'membership_status',
                    'membership',
                    'pending_membership',
                    'application_step'
                ]
            ])
            ->assertJsonPath('data.application_step', 'personal_details');
    }
}
