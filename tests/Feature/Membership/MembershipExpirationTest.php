<?php

namespace Tests\Feature\Membership;

use App\Models\Membership;
use App\Models\MembershipTier;
use App\Models\User;
use App\Services\Membership\MembershipExpirationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipExpirationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed basic membership tiers
        $this->seed(\Database\Seeders\MembershipTiersSeeder::class);
    }

    public function test_expired_membership_is_marked_as_expired_and_user_receives_free_tier(): void
    {
        $user = User::factory()->create();

        $goldTier = MembershipTier::where('code', 'gold')->first();
        $basicTier = MembershipTier::where('code', 'basic')->first();

        // Create expired active membership
        $expiredMembership = Membership::create([
            'user_id' => $user->id,
            'membership_tier_id' => $goldTier->id,
            'status' => 'active',
            'started_at' => now()->subYear(),
            'expires_at' => now()->subDay(),
        ]);

        $service = new MembershipExpirationService();
        $count = $service->processExpirations();

        $this->assertEquals(1, $count);

        // Check original membership is expired
        $this->assertDatabaseHas('memberships', [
            'id' => $expiredMembership->id,
            'status' => 'expired',
        ]);

        // Check user now has an active free tier membership
        $currentMembership = $user->fresh()->currentMembership;
        $this->assertNotNull($currentMembership);
        $this->assertEquals($basicTier->id, $currentMembership->membership_tier_id);
        $this->assertEquals('active', $currentMembership->status);
        $this->assertNull($currentMembership->expires_at);
    }

    public function test_active_unexpired_membership_is_not_expired(): void
    {
        $user = User::factory()->create();
        $goldTier = MembershipTier::where('code', 'gold')->first();

        $activeMembership = Membership::create([
            'user_id' => $user->id,
            'membership_tier_id' => $goldTier->id,
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $service = new MembershipExpirationService();
        $count = $service->processExpirations();

        $this->assertEquals(0, $count);

        $this->assertDatabaseHas('memberships', [
            'id' => $activeMembership->id,
            'status' => 'active',
        ]);
    }

    public function test_lifetime_membership_with_null_expires_at_is_never_expired(): void
    {
        $user = User::factory()->create();
        $sovereignTier = MembershipTier::where('code', 'sovereign')->first();

        $lifetimeMembership = Membership::create([
            'user_id' => $user->id,
            'membership_tier_id' => $sovereignTier->id,
            'status' => 'active',
            'started_at' => now()->subYears(2),
            'expires_at' => null,
        ]);

        $service = new MembershipExpirationService();
        $count = $service->processExpirations();

        $this->assertEquals(0, $count);

        $this->assertDatabaseHas('memberships', [
            'id' => $lifetimeMembership->id,
            'status' => 'active',
        ]);
    }

    public function test_if_no_free_tier_exists_user_is_left_with_no_active_membership(): void
    {
        // Deactivate all free tiers
        MembershipTier::query()->update(['price_amount' => 5000, 'price' => 50.00]);

        $user = User::factory()->create();
        $tier = MembershipTier::first();

        $expiredMembership = Membership::create([
            'user_id' => $user->id,
            'membership_tier_id' => $tier->id,
            'status' => 'active',
            'started_at' => now()->subYear(),
            'expires_at' => now()->subMinute(),
        ]);

        $service = new MembershipExpirationService();
        $count = $service->processExpirations();

        $this->assertEquals(1, $count);

        $this->assertDatabaseHas('memberships', [
            'id' => $expiredMembership->id,
            'status' => 'expired',
        ]);

        $this->assertNull($user->fresh()->currentMembership);
    }

    public function test_artisan_command_executes_successfully(): void
    {
        $user = User::factory()->create();
        $goldTier = MembershipTier::where('code', 'gold')->first();

        Membership::create([
            'user_id' => $user->id,
            'membership_tier_id' => $goldTier->id,
            'status' => 'active',
            'started_at' => now()->subYear(),
            'expires_at' => now()->subHour(),
        ]);

        $this->artisan('memberships:expire')
            ->expectsOutput('Checking for expired memberships...')
            ->expectsOutput('Processed 1 expired membership(s).')
            ->assertExitCode(0);
    }
}
