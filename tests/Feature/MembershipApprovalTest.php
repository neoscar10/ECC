<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\MembershipTier;
use App\Domain\Membership\MembershipApplication;

class MembershipApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $admin;
    protected $token;
    protected $adminToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\PrivilegesSeeder::class);
        $this->seed(\Database\Seeders\MembershipTiersSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('user');

        $this->admin = User::factory()->create(['email' => 'admin@ecc.com']);
        $this->admin->assignRole('ecc_admin');
    }

    public function test_basic_tier_auto_approves()
    {
        $tier = MembershipTier::where('code', 'basic')->first();
        $application = MembershipApplication::create([
            'user_id' => $this->user->id,
            'status' => 'draft',
            'current_step' => 'payment',
            'membership_tier_id' => $tier->id,
            'payment_status' => 'test_paid' // simulate paid
        ]);

        $response = $this->actingAs($this->user, 'api')
                         ->postJson("/api/v1/membership-applications/{$application->id}/submit");

        $response->assertStatus(200)
                 ->assertJsonFragment(['next_step' => 'access_granted'])
                 ->assertJsonFragment(['status' => 'active']);

        $this->assertDatabaseHas('memberships', [
            'user_id' => $this->user->id,
            'membership_tier_id' => $tier->id,
            'status' => 'active'
        ]);
    }

    public function test_gold_tier_requires_approval()
    {
        $tier = MembershipTier::where('code', 'gold')->first();
        $application = MembershipApplication::create([
            'user_id' => $this->user->id,
            'status' => 'draft',
            'current_step' => 'payment',
            'membership_tier_id' => $tier->id,
            'payment_status' => 'test_paid'
        ]);

        $response = $this->actingAs($this->user, 'api')
                         ->postJson("/api/v1/membership-applications/{$application->id}/submit");

        $response->assertStatus(200)
                 ->assertJsonFragment(['next_step' => 'waiting_approval'])
                 ->assertJsonFragment(['status' => 'pending']);

        $this->assertDatabaseHas('memberships', [
            'user_id' => $this->user->id,
            'membership_tier_id' => $tier->id,
            'status' => 'pending'
        ]);

        // Check status endpoint
        $statusResponse = $this->actingAs($this->user, 'api')
                               ->getJson('/api/v1/membership/status');
        
        $statusResponse->assertStatus(200)
                       ->assertJsonFragment(['membership_status' => 'pending']);
    }

    public function test_admin_can_approve_membership()
    {
        // Create pending membership
        $tier = MembershipTier::where('code', 'gold')->first();
        $membership = \App\Models\Membership::create([
            'user_id' => $this->user->id,
            'membership_tier_id' => $tier->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->admin, 'api')
                         ->patchJson("/api/v1/admin/memberships/{$membership->id}/approve");

        $response->assertStatus(200)
                 ->assertJsonFragment(['status' => 'active']);

        $this->assertDatabaseHas('memberships', [
            'id' => $membership->id,
            'status' => 'active',
            'approved_by' => $this->admin->id
        ]);
    }
}
