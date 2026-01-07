<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\MembershipTier;
use App\Domain\Membership\MembershipApplication;

class MembershipTierTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\PrivilegesSeeder::class);
        $this->seed(\Database\Seeders\MembershipTiersSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('user');
        $this->token = auth('api')->login($this->user);
    }

    public function test_can_list_membership_tiers()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->getJson('/api/v1/membership-tiers');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => ['id', 'name', 'code', 'price_amount', 'privileges', 'features']
                     ]
                 ]);
        
        $response->assertJsonFragment(['code' => 'gold']);
    }

    public function test_can_view_single_tier()
    {
        $tier = MembershipTier::where('code', 'gold')->first();
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->getJson("/api/v1/membership-tiers/{$tier->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Gold']);
    }

    public function test_can_select_tier()
    {
        // Setup application
        $application = MembershipApplication::create([
            'user_id' => $this->user->id,
            'status' => 'draft',
            'current_step' => 'collector_intent'
        ]);

        $tier = MembershipTier::where('code', 'gold')->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->postJson("/api/v1/membership-applications/{$application->id}/select-tier", [
                             'tier_id' => $tier->id
                         ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['membership_tier_id' => $tier->id]);
        
        $this->assertDatabaseHas('membership_applications', [
            'id' => $application->id,
            'membership_tier_id' => $tier->id
        ]);
    }
    
    public function test_cannot_select_invalid_tier()
    {
        $application = MembershipApplication::create([
            'user_id' => $this->user->id,
            'status' => 'draft',
            'current_step' => 'collector_intent'
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->postJson("/api/v1/membership-applications/{$application->id}/select-tier", [
                             'tier_id' => 99999
                         ]);

        if ($response->status() !== 422) {
            $response->dump();
        }

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['tier_id']);
    }
}
