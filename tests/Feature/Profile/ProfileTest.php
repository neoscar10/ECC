<?php

namespace Tests\Feature\Profile;

use App\Models\MembershipTier;
use App\Models\MembershipTierFeature;
use App\Models\Privilege;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $tier;

    protected function setUp(): void
    {
        parent::setUp();
        // Create user
        $this->user = User::factory()->create();
        
        // Setup tiers and privileges
        $this->tier = MembershipTier::create([
            'code' => 'platinum',
            'name' => 'Platinum Tier',
            'price' => 1000,
            'duration_days' => 365,
            'is_active' => true,
        ]);

        $privilege = Privilege::create([
            'key' => 'private_viewing',
            'name' => 'Private Viewing',
            'description' => 'Access to private viewings',
            'is_active' => true,
        ]);

        $this->tier->privileges()->attach($privilege);
    }

    public function test_guest_cannot_access_profile_endpoints()
    {
        $this->getJson('/api/v1/profile')->assertStatus(401);
        $this->patchJson('/api/v1/profile', [])->assertStatus(401);
        $this->postJson('/api/v1/profile/avatar', [])->assertStatus(401);
        $this->getJson('/api/v1/profile/membership')->assertStatus(401);
    }

    public function test_get_profile_structure()
    {
        // Add avatars
        $this->user->update(['avatar_path' => 'users/avatar.jpg']);

        $response = $this->actingAs($this->user, 'api')->getJson('/api/v1/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => [
                        'id', 'name', 'email', 'avatar_url', 'avatar_required'
                    ],
                    'membership'
                ]
            ]);
            
        // Check avatar URL generation
        $this->assertNotNull($response->json('data.user.avatar_url'));
        $this->assertFalse($response->json('data.user.avatar_required'));
    }

    public function test_update_profile_details()
    {
        $payload = [
            'full_name' => 'John Updated',
            'city' => 'Dubai',
            'date_of_birth' => '1990-01-01',
        ];

        $response = $this->actingAs($this->user, 'api')->patchJson('/api/v1/profile', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.user.full_name', 'John Updated')
            ->assertJsonPath('data.user.city', 'Dubai');

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'full_name' => 'John Updated',
            'city' => 'Dubai',
        ]);
    }

    public function test_upload_avatar()
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->actingAs($this->user, 'api')->postJson('/api/v1/profile/avatar', [
            'avatar' => $file
        ]);

        $response->assertStatus(200);
        
        // Verify DB update
        $this->user->refresh();
        $this->assertNotNull($this->user->avatar_path);
        
        // Verify storage
        Storage::disk('public')->assertExists($this->user->avatar_path);
    }

    public function test_get_membership_details_with_privileges()
    {
        // Assign membership
        $this->user->memberships()->create([
            'membership_tier_id' => $this->tier->id,
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $response = $this->actingAs($this->user, 'api')->getJson('/api/v1/profile/membership');

        $response->assertStatus(200)
            ->assertJsonPath('data.tier.id', $this->tier->id)
            ->assertJsonStructure([
                'data' => [
                    'tier',
                    'status',
                    'privileges' => [
                        '*' => ['id', 'key', 'name']
                    ]
                ]
            ]);
            
        // Check privilege presence
        $this->assertTrue(count($response->json('data.privileges')) > 0);
        $this->assertEquals('private_viewing', $response->json('data.privileges.0.key'));
    }
}
