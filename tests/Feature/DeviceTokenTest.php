<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDeviceToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class DeviceTokenTest extends TestCase
{
    use RefreshDatabase;

    protected function getAuthHeaders(User $user)
    {
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    public function test_can_register_new_token()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/me/device-tokens', [
                'token' => 'fcm-token-123',
                'platform' => 'android',
                'device_id' => 'device-abc',
            ], $this->getAuthHeaders($user));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('user_device_tokens', [
            'user_id' => $user->id,
            'token' => 'fcm-token-123',
            'platform' => 'android',
        ]);
    }

    public function test_registering_existing_token_updates_last_seen_and_claims_ownership()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // User 1 registers token
        $user1->deviceTokens()->create([
            'token' => 'shared_token',
            'platform' => 'ios',
            'last_seen_at' => now()->subDay(),
        ]);

        // User 2 registers SAME token
        $response = $this->postJson('/api/v1/me/device-tokens', [
                'token' => 'shared_token',
                'platform' => 'ios',
            ], $this->getAuthHeaders($user2));

        $response->assertStatus(200);

        // Check DB: Should belong to User 2 now
        $this->assertEquals(1, UserDeviceToken::where('token', 'shared_token')->count());
        $token = UserDeviceToken::where('token', 'shared_token')->first();
        
        $this->assertEquals($user2->id, $token->user_id);
        $this->assertTrue($token->last_seen_at->isToday());
    }

    public function test_can_list_tokens()
    {
        $user = User::factory()->create();
        $user->deviceTokens()->create(['token' => 't1', 'platform' => 'android']);
        $user->deviceTokens()->create(['token' => 't2', 'platform' => 'ios']);

        $response = $this->getJson('/api/v1/me/device-tokens', $this->getAuthHeaders($user));

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_unregister_token()
    {
        $user = User::factory()->create();
        $user->deviceTokens()->create(['token' => 'to_del', 'platform' => 'android']);

        $response = $this->postJson('/api/v1/me/device-tokens/unregister', [
                'token' => 'to_del'
            ], $this->getAuthHeaders($user));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('user_device_tokens', ['token' => 'to_del']);
    }

    public function test_user_cannot_delete_others_token()
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();

        $token = $owner->deviceTokens()->create(['token' => 'private', 'platform' => 'ios']);

        $response = $this->deleteJson("/api/v1/me/device-tokens/{$token->id}", [], $this->getAuthHeaders($attacker));

        $response->assertStatus(404);
        $this->assertDatabaseHas('user_device_tokens', ['id' => $token->id]);
    }

    public function test_user_can_delete_own_token()
    {
        $owner = User::factory()->create();
        $token = $owner->deviceTokens()->create(['token' => 'private', 'platform' => 'ios']);

        $this->deleteJson("/api/v1/me/device-tokens/{$token->id}", [], $this->getAuthHeaders($owner))
             ->assertStatus(200);

        $this->assertDatabaseMissing('user_device_tokens', ['id' => $token->id]);
    }
}
