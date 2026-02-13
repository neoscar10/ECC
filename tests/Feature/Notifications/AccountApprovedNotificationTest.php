<?php

namespace Tests\Feature\Notifications;

use App\Domain\Membership\MembershipApplication;
use App\Jobs\Notifications\SendFcmToUserJob;
use App\Livewire\Admin\Membership\Applications\Index;
use App\Models\User;
use App\Models\MembershipTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class AccountApprovedNotificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_dispatches_notification_when_application_is_approved()
    {
        Queue::fake();

        $user = User::factory()->create();
        $tier = MembershipTier::factory()->create(['name' => 'Gold', 'duration_days' => 365]);
        $application = MembershipApplication::factory()->create([
            'user_id' => $user->id,
            'selected_tier_id' => $tier->id,
            'status' => 'submitted',
        ]);

        $role = \Spatie\Permission\Models\Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole($role);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmApprove', $application->id)
            ->call('approve');

        Queue::assertPushed(SendFcmToUserJob::class, function ($job) use ($user, $tier) {
            return $job->userId === $user->id &&
                   $job->title === 'Account Approved' &&
                   $job->body === 'Your ECC account has been approved. You can now access member features.' &&
                   $job->data['type'] === 'account_approved' &&
                   $job->data['target_page'] === 'account_status' &&
                   $job->data['target_id'] === (string)$user->id &&
                   $job->data['membership_tier_id'] === (string)$tier->id &&
                   $job->data['status'] === 'approved';
        });
    }

    /** @test */
    public function it_does_not_dispatch_notification_if_already_approved()
    {
        Queue::fake();

        $user = User::factory()->create();
        $tier = MembershipTier::factory()->create();
        $application = MembershipApplication::factory()->create([
            'user_id' => $user->id,
            'selected_tier_id' => $tier->id,
            'status' => 'submitted',
        ]);

        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole($role);

        // First approval
        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmApprove', $application->id)
            ->call('approve');

        Queue::assertPushed(SendFcmToUserJob::class, 1);

        // Second approval attempt (should fail or return early)
        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmApprove', $application->id)
            ->call('approve');

        // Should still be 1 (deduped or blocked by status check)
        Queue::assertPushed(SendFcmToUserJob::class, 1);
    }
}
