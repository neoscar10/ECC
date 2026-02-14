<?php

namespace Tests\Feature;

use App\Models\MembershipTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipTierHasVaultAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_vault_access_is_casted_to_boolean()
    {
        $tier = MembershipTier::factory()->create(['has_vault_access' => 1]);
        $this->assertTrue($tier->has_vault_access);
        $this->assertIsBool($tier->has_vault_access);

        $tier2 = MembershipTier::factory()->create(['has_vault_access' => 0]);
        $this->assertFalse($tier2->has_vault_access);
        $this->assertIsBool($tier2->has_vault_access);
    }
}
