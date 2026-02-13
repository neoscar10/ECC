<?php

namespace Tests\Feature\Api;

use App\Domain\Membership\MembershipApplication;
use App\Models\User;
use App\Models\MembershipTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipApplicationErrorTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $application;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'phone_verified_at' => now(),
            'phone' => '+1234567890' // Assuming phone field exists and is needed
        ]);
        $this->application = MembershipApplication::factory()->create(['user_id' => $this->user->id]);
    }

    /** @test */
    public function personal_details_validation_returns_generic_error()
    {
        $response = $this->actingAs($this->user, 'api')
            ->patchJson("/api/v1/membership-applications/{$this->application->id}/personal-details", []);
        
        // Target behavior: "Validation failed: Personal details are incomplete."
        $response->assertStatus(422)
            ->assertJson(['message' => 'Validation failed: Personal details are incomplete.'])
            ->assertJsonStructure(['errors' => ['full_name', 'date_of_birth', 'country', 'city']]);
    }

    /** @test */
    public function cricket_profile_validation_returns_generic_error()
    {
        $response = $this->actingAs($this->user, 'api')
            ->patchJson("/api/v1/membership-applications/{$this->application->id}/cricket-profile", []);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Validation failed: Cricket profile data is invalid.'])
            ->assertJsonStructure(['errors' => ['preferred_formats', 'eras']]);
    }

    /** @test */
    public function collector_intent_validation_returns_generic_error()
    {
        $response = $this->actingAs($this->user, 'api')
            ->patchJson("/api/v1/membership-applications/{$this->application->id}/collector-intent", []);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Validation failed: Collector intent data is invalid.'])
            ->assertJsonStructure(['errors' => ['has_acquired_memorabilia_before', 'focus', 'investment_horizon']]);
    }

    /** @test */
    public function select_tier_validation_returns_generic_error()
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/membership-applications/{$this->application->id}/select-tier", ['tier_id' => 9999]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Validation failed: Invalid tier selection.'])
            ->assertJsonStructure(['errors' => ['tier_id']]);
    }

    /** @test */
    public function submit_application_payment_required_error()
    {
        // Tier requires payment, but status is unpaid
        $tier = MembershipTier::create([
            'name' => 'Paid Tier', 
            'code' => 'PAID', // Added missing code
            'price' => 100, 
            'is_active' => true
        ]);
        $this->application->update(['selected_tier_id' => $tier->id, 'payment_status' => 'unpaid']);

        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/membership-applications/{$this->application->id}/submit");

        $response->assertStatus(400); 
    }

    /** @test */
    public function application_not_found_error()
    {
        $otherUser = User::factory()->create([
            'phone_verified_at' => now(),
            'phone' => '+1987654321'
        ]);
        
        // User tries to access their own non-existent application
        $response = $this->actingAs($otherUser, 'api')
            ->patchJson("/api/v1/membership-applications/{$this->application->id}/personal-details", [
                'full_name' => 'Test',
                'date_of_birth' => '1990-01-01',
                'country' => 'UK',
                'city' => 'London'
            ]);

        // Current behavior: 404 HTML or generic
        // Target behavior: 404 JSON with "Application not found."
        file_put_contents(storage_path('logs/debug_404.json'), json_encode($response->json(), JSON_PRETTY_PRINT));
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Membership application not found.'
            ]);
    }
}
