<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\MembershipTier;
use App\Models\Membership;
use App\Models\MembershipApplication;
use App\Mail\AccountCreatedMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class UserCreationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $tier;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tier = MembershipTier::factory()->create(['name' => 'Gold', 'is_active' => true]);
        
        // Use firstOrCreate to avoid conflicts if roles are already seeded
        $adminRole = Role::firstOrCreate(['name' => 'ecc_admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole($adminRole);
    }

    /** @test */
    public function admin_can_create_user_with_auto_password()
    {
        Mail::fake();

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
        ];

        $service = new \App\Services\Admin\AdminUserCreationService();
        $user = $service->createAdminUser($userData, $this->tier->id);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'phone' => '1234567890'
        ]);
        
        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user->phone_verified_at);
        
        $this->assertDatabaseHas('memberships', [
            'user_id' => $user->id,
            'membership_tier_id' => $this->tier->id,
            'status' => 'active'
        ]);

        Mail::assertQueued(AccountCreatedMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) && 
                   $mail->tier->id === $this->tier->id && 
                   strlen($mail->password) === 12;
        });
    }

    /** @test */
    public function admin_can_create_user_with_manual_password()
    {
        Mail::fake();

        $userData = [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'phone' => '0987654321',
        ];

        $service = new \App\Services\Admin\AdminUserCreationService();
        $user = $service->createAdminUser($userData, $this->tier->id, [], 'Secret123!');

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
        
        Mail::assertQueued(AccountCreatedMail::class, function ($mail) {
            return $mail->password === 'Secret123!';
        });
    }

    /** @test */
    public function admin_can_create_user_with_optional_application_data()
    {
        Mail::fake();

        $userData = [
            'name' => 'Cricket Fan',
            'email' => 'fan@example.com',
            'phone' => '5556667777',
        ];

        $applicationData = [
            'personal' => [
                'full_name' => 'Cricket Fan Official',
                'dob' => '1990-01-01',
                'country' => 'India',
                'city' => 'Mumbai',
            ],
            'cricket' => [
                'preferred_formats' => ['test', 't20'],
                'eras' => ['modern'],
            ],
            'collector' => [
                'has_acquired_memorabilia_before' => 'yes',
                'focus' => 'rarity',
                'investment_horizon' => 10,
            ],
        ];

        $service = new \App\Services\Admin\AdminUserCreationService();
        $user = $service->createAdminUser($userData, $this->tier->id, $applicationData);

        $this->assertDatabaseHas('users', [
            'email' => 'fan@example.com',
            'full_name' => 'Cricket Fan Official'
        ]);

        $this->assertDatabaseHas('membership_applications', [
            'user_id' => $user->id,
            'selected_tier_id' => $this->tier->id,
            'status' => 'submitted'
        ]);

        $app = MembershipApplication::where('user_id', $user->id)->first();
        $this->assertEquals('India', $app->personal_details_json['country']);
        $this->assertContains('TEST', $app->cricket_profile_json['preferred_formats']);
    }

    /** @test */
    public function creation_is_transactional_and_fails_on_duplicate_email()
    {
        User::factory()->create(['email' => 'duplicate@example.com']);
        
        $userData = [
            'name' => 'Duplicate User',
            'email' => 'duplicate@example.com',
            'phone' => '1111111111',
        ];

        $service = new \App\Services\Admin\AdminUserCreationService();

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        $service->createAdminUser($userData, $this->tier->id);

        $this->assertDatabaseMissing('memberships', [
            'membership_tier_id' => $this->tier->id
        ]);
    }
}
