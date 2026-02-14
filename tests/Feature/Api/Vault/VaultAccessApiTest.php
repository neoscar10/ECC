<?php

namespace Tests\Feature\Api\Vault;

use App\Models\User;
use App\Models\MembershipTier;
use App\Models\UserVaultItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class VaultAccessApiTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        config([
            'jwt.secret' => 'testing_secret_key_1234567890_testing_secret_key',
            'auth.guards.api.driver' => 'jwt',
            'auth.guards.api.provider' => 'users',
        ]);
    }

    /** @test */
    public function user_with_vault_access_can_view_vault_items()
    {
        $tier = MembershipTier::factory()->create(['has_vault_access' => true]);
        $user = User::factory()->create();
        $user->membership()->create([
            'membership_tier_id' => $tier->id,
            'status' => 'active',
            'started_at' => now(),
        ]);

        UserVaultItem::factory()->create([
            'user_id' => $user->id,
            'status' => 'locked',
            'item_title' => 'Test Item',
        ]);



        dd(get_class(auth('api')));
        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);
        
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                         ->getJson('/api/v1/me/vault');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'item_title',
                        'status',
                    ]
                ]
            ])
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function user_without_vault_access_cannot_view_vault_items()
    {
        $tier = MembershipTier::factory()->create(['has_vault_access' => false]);
        $user = User::factory()->create();
        $user->membership()->create([
            'membership_tier_id' => $tier->id,
            'status' => 'active',
            'started_at' => now(),
        ]);

        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                         ->getJson('/api/v1/me/vault');

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'forbidden_tier_access',
            ]);
    }
}
