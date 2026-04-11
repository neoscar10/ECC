<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Services\Admin\AdminOperationalAttentionService;
use App\Models\MembershipApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class AdminOperationalAttentionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Setup super_admin role
        Role::create(['name' => 'super_admin']);
    }

    public function test_aggregates_membership_applications()
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        MembershipApplication::factory()->create(['status' => 'submitted']);
        MembershipApplication::factory()->create(['status' => 'pending']);
        MembershipApplication::factory()->create(['status' => 'approved']); // Should NOT be counted

        $service = new AdminOperationalAttentionService();
        $summary = $service->getAttentionSummary($admin);

        // Expecting 2: submitted and pending
        $this->assertEquals(2, $summary['total_count']);
        
        $membershipItem = $summary['grouped']['requests']->firstWhere('id', 'membership_applications');
        $this->assertNotNull($membershipItem);
        $this->assertEquals(2, $membershipItem['count']);
    }

    public function test_respects_permissions()
    {
        // Create a role that is NOT super_admin or ecc_admin
        Role::create(['name' => 'editor']);
        $user = User::factory()->create();
        $user->assignRole('editor');

        MembershipApplication::factory()->create(['status' => 'submitted']);

        $service = new AdminOperationalAttentionService();
        $summary = $service->getAttentionSummary($user);

        // Expecting 0 because editor doesn't have permissions in the current logic
        $this->assertEquals(0, $summary['total_count']);
    }
}
