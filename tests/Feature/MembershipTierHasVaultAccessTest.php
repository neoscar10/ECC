<?php

namespace Tests\Feature;

use App\Models\MembershipTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MembershipTierHasVaultAccessTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_vault_access_column()
    {
        $tier = MembershipTier::factory()->create([
            'has_vault_access' => true,
        ]);

        $this->assertTrue($tier->has_vault_access);
        
        $tier2 = MembershipTier::factory()->create([
            'has_vault_access' => false,
        ]);

        $this->assertFalse($tier2->has_vault_access);
    }

    /** @test */
    public function it_casts_has_vault_access_to_boolean()
    {
        $tier = MembershipTier::factory()->create([
            'has_vault_access' => 1,
        ]);

        $this->assertIsBool($tier->has_vault_access);
        $this->assertTrue($tier->has_vault_access);
    }
}
