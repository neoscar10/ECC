<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\MembershipApplication;
use App\Models\MembershipTier;
use App\Models\Payment;
use App\Support\Payments\PaymentPurpose;
use App\Support\Payments\PaymentStatus;
use App\Livewire\Membership\Application\Step6SelectTier;
use App\Livewire\Membership\Application\Step7Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\TestCase;

class MembershipWebWizardTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected MembershipTier $freeTier;
    protected MembershipTier $paidTier;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\MembershipTiersSeeder::class);

        $this->user = User::factory()->create([
            'id' => 99,
            'phone_verified_at' => now(),
            'phone' => '+447700900000',
        ]);
        $this->user->assignRole('user');

        $this->freeTier = MembershipTier::where('price', 0)->first();
        $this->paidTier = MembershipTier::where('price', '>', 0)->first();
    }

    /** @test */
    public function test_free_tier_selection_submits_application_immediately_and_redirects_to_step8()
    {
        $application = MembershipApplication::create([
            'user_id' => $this->user->id,
            'status' => 'draft',
            'current_step' => 'personal_details',
            'personal_details_json' => [
                'full_name' => 'John Free Cricketer',
                'date_of_birth' => '1990-05-15',
                'country' => 'India',
                'city' => 'Mumbai'
            ]
        ]);

        $this->actingAs($this->user);

        Livewire::test(Step6SelectTier::class)
            ->set('selectedTierId', $this->freeTier->id)
            ->call('submit')
            ->assertRedirect(route('membership.application.step8'));

        $application->refresh();
        $this->assertEquals('submitted', $application->status);
        $this->assertEquals('not_required', $application->payment_status);
        $this->assertDatabaseHas('memberships', [
            'user_id' => $this->user->id,
            'membership_tier_id' => $this->freeTier->id,
            'status' => 'active', // Because basic does not require approval
        ]);
    }

    /** @test */
    public function test_step7_redirects_free_tier_to_step8_on_mount()
    {
        $application = MembershipApplication::create([
            'user_id' => $this->user->id,
            'status' => 'draft',
            'current_step' => 'payment',
            'selected_tier_id' => $this->freeTier->id,
            'membership_tier_id' => $this->freeTier->id,
            'personal_details_json' => [
                'full_name' => 'John Free Cricketer',
                'date_of_birth' => '1990-05-15',
                'country' => 'India',
                'city' => 'Mumbai'
            ]
        ]);

        $this->actingAs($this->user);

        Livewire::test(Step7Payment::class)
            ->assertRedirect(route('membership.application.step8'));

        $application->refresh();
        $this->assertEquals('submitted', $application->status);
        $this->assertEquals('not_required', $application->payment_status);
    }

    /** @test */
    public function test_middleware_allows_payment_routes_even_if_registration_is_incomplete()
    {
        // Application is in draft/unpaid status
        $application = MembershipApplication::create([
            'user_id' => $this->user->id,
            'status' => 'draft',
            'current_step' => 'payment',
            'selected_tier_id' => $this->paidTier->id,
            'membership_tier_id' => $this->paidTier->id,
            'personal_details_json' => [
                'full_name' => 'John Paid Cricketer',
                'date_of_birth' => '1990-05-15',
                'country' => 'India',
                'city' => 'Mumbai'
            ],
            'cricket_profile_json' => [
                'preferred_formats' => ['TEST'],
            ],
            'collector_intent_json' => [
                'history' => 'yes',
                'focus' => 'rarity',
                'horizon_value' => 5,
            ]
        ]);

        // Create a pending payment
        $payment = Payment::create([
            'user_id' => $this->user->id,
            'payable_type' => MembershipApplication::class,
            'payable_id' => $application->id,
            'amount' => $this->paidTier->price,
            'currency' => 'INR',
            'status' => PaymentStatus::PENDING,
            'purpose' => PaymentPurpose::MEMBERSHIP_RENEWAL,
            'gateway' => 'razorpay',
        ]);

        $this->actingAs($this->user);

        // Accessing /payments/{payment}/pay should NOT redirect to step-7
        // (It will redirect to the gateway specific pay route, in this case payments.razorpay.pay, which is expected)
        $response = $this->get(route('payments.pay', $payment->id));
        $response->assertRedirect(route('payments.razorpay.pay', $payment->id));
    }

    /** @test */
    public function test_middleware_redirects_non_payment_non_wizard_routes_when_registration_incomplete()
    {
        $application = MembershipApplication::create([
            'user_id' => $this->user->id,
            'status' => 'draft',
            'current_step' => 'payment',
            'selected_tier_id' => $this->paidTier->id,
            'membership_tier_id' => $this->paidTier->id,
            'personal_details_json' => [
                'full_name' => 'John Paid Cricketer',
                'date_of_birth' => '1990-05-15',
                'country' => 'India',
                'city' => 'Mumbai'
            ],
            'cricket_profile_json' => [
                'preferred_formats' => ['TEST'],
            ],
            'collector_intent_json' => [
                'history' => 'yes',
                'focus' => 'rarity',
                'horizon_value' => 5,
            ]
        ]);

        $this->actingAs($this->user);

        // Accessing /home should redirect to Step 7
        $response = $this->get(route('home'));
        $response->assertRedirect(route('membership.application.step7'));
    }
}
