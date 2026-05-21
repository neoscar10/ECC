<?php

namespace Tests\Feature\Notifications;

use App\Models\MembershipApplication;
use App\Jobs\Notifications\SendFcmToUserJob;
use App\Livewire\Admin\Membership\Applications\Index;
use App\Models\User;
use App\Models\MembershipTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Tests\TestCase;

class AccountApprovedNotificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_dispatches_notification_sync_and_logs_success_when_tokens_exist()
    {
        Queue::fake();
        Log::spy();

        $user = User::factory()->create();
        // Create a device token for the user
        $user->deviceTokens()->create(['token' => 'test-token', 'is_active' => true]);

        $tier = MembershipTier::factory()->create(['name' => 'Gold', 'duration_days' => 365]);
        $application = MembershipApplication::factory()->create([
            'user_id' => $user->id,
            'selected_tier_id' => $tier->id,
            'status' => 'submitted',
        ]);

        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole($role);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmApprove', $application->id)
            ->call('approve');

        // Assert job WAS NOT pushed to queue (since it should be sync)
        // However, standard Queue::fake() interacts with dispatchSync by executing it immediately unless configured otherwise.
        // But we want to ensure it was dispatched via the sync mechanism.
        // For simplicity and robustness with Queue::fake(), checking that the job class was instantiated and handled is hard without mocking the job itself.
        // Instead, we rely on the side effects: the LOGS.
        
        Log::shouldHaveReceived('info')->with('ACCOUNT_APPROVED_SEND_START', \Mockery::on(function ($data) use ($user) {
            return $data['user_id'] === $user->id && $data['tokens_count'] >= 1;
        }));

        Log::shouldHaveReceived('info')->with('ACCOUNT_APPROVED_SEND_SUCCESS', ['user_id' => $user->id]);
        
        // Also verify the job logic ran (which logs "Job [SendFcmToUserJob] starting")
        // But since we are spying Log, we can check for that too if we want, but the specific requirements are about the new logs.
    }

    /** @test */
    public function it_skips_dispatch_and_logs_when_no_tokens_exist()
    {
        Queue::fake();
        Log::spy();

        $user = User::factory()->create();
        // No device tokens created

        $tier = MembershipTier::factory()->create();
        $application = MembershipApplication::factory()->create([
            'user_id' => $user->id,
            'selected_tier_id' => $tier->id,
            'status' => 'submitted',
        ]);

        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole($role);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('confirmApprove', $application->id)
            ->call('approve');

        Log::shouldHaveReceived('info')->with('ACCOUNT_APPROVED_SEND_SKIPPED_NO_TOKENS', ['user_id' => $user->id]);
        
        // Ensure START and SUCCESS were NOT logged
        Log::shouldNotHaveReceived('info')->with('ACCOUNT_APPROVED_SEND_START', \Mockery::any());
        Log::shouldNotHaveReceived('info')->with('ACCOUNT_APPROVED_SEND_SUCCESS', \Mockery::any());

        // Ensure Job was NOT pushed/dispatched (since we skip it in controller)
        Queue::assertNotPushed(SendFcmToUserJob::class);
    }
}
