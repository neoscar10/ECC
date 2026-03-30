<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use App\Models\UserDeviceToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed roles for testing
        $this->seed(\Database\Seeders\RoleSeeder::class);
        
        // Mock FCM Manager to avoid external calls and missing bindings
        $mockFCM = \Mockery::mock(\App\Services\Notifications\FcmTopicManager::class);
        $mockFCM->shouldReceive('unsubscribeTokensFromTopic')->zeroOrMoreTimes();
        $this->instance(\App\Services\Notifications\FcmTopicManager::class, $mockFCM);
    }

    /**
     * Test that an authenticated user can delete their own account.
     */
    public function test_authenticated_user_can_delete_own_account()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        
        // Add a device token to verify it's deleted (Manually since factory is missing)
        $token = UserDeviceToken::create([
            'user_id' => $user->id,
            'token' => 'test-token-' . uniqid(),
            'platform' => 'android',
        ]);

        $jwtToken = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $jwtToken)
            ->deleteJson('/api/v1/auth/me/account');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Account deleted successfully.',
            ]);

        // Verify soft delete
        $this->assertSoftDeleted('users', ['id' => $user->id]);
        
        // Verify device token is deleted
        $this->assertDatabaseMissing('user_device_tokens', ['id' => $token->id]);

        // Verify token is invalidated by trying to access a protected route
        // We use a fresh request for this
        $responseAfter = $this->withHeader('Authorization', 'Bearer ' . $jwtToken)
            ->getJson('/api/v1/auth/me');
            
        $responseAfter->assertStatus(401);
    }

    /**
     * Test that an unauthenticated user cannot delete an account.
     */
    public function test_unauthenticated_user_cannot_delete_account()
    {
        $response = $this->deleteJson('/api/v1/auth/me/account');

        $response->assertStatus(401);
    }

    /**
     * Test that a deleted user cannot login.
     */
    public function test_deleted_user_cannot_login()
    {
        $email = 'deleted@example.com';
        $password = 'password';
        $user = User::factory()->create([
            'email' => $email,
            'password' => \Illuminate\Support\Facades\Hash::make($password),
        ]);
        $user->assignRole('user');
        
        // Soft delete the user
        $user->delete();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        // Existing login logic returns 404 if user not found (which soft-deleted user is not found by default)
        $response->assertStatus(404);
    }
}
