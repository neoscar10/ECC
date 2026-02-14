<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Archive\Orders\Create;
use App\Livewire\Admin\Auctions\Orders\RecordSaleModal;
use App\Models\Archive\ArchiveProduct;
use App\Models\Auctions\AuctionLot;
use App\Models\MembershipTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecordSaleVaultIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        // Create role if it doesn't exist (Spatie)
        \Spatie\Permission\Models\Role::create(['name' => 'ecc_admin', 'guard_name' => 'web']);
        
        $user = User::factory()->create();
        $user->assignRole('ecc_admin');
        
        $this->actingAs($user);
    }

    /** @test */
    public function admin_can_lock_archive_product_in_vault_for_eligible_user()
    {
        $tier = MembershipTier::factory()->create(['has_vault_access' => true]);
        $user = User::factory()->create();
        $user->membership()->create(['membership_tier_id' => $tier->id, 'status' => 'active', 'started_at' => now()]);
        
        $product = ArchiveProduct::factory()->create(['quantity' => 1]);

        Livewire::test(Create::class)
            ->set('product_id', $product->id)
            ->call('selectProduct', $product->id)
            ->set('buyer_type', 'registered')
            ->set('user_id', $user->id)
            ->call('selectUser', $user->id) // Simulate selection which checks access
            ->set('qty', 1)
            ->set('unit_price_inr', 1000)
            ->set('fulfillment_method', 'vault')
            ->call('store');

        $this->assertDatabaseHas('orders', [
            'archive_product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('user_vault_items', [
            'user_id' => $user->id,
            'source_id' => $product->id,
            'status' => 'locked',
        ]);
    }

    /** @test */
    public function admin_cannot_lock_in_vault_for_ineligible_user()
    {
        $tier = MembershipTier::factory()->create(['has_vault_access' => false]);
        $user = User::factory()->create();
        $user->membership()->create(['membership_tier_id' => $tier->id, 'status' => 'active', 'started_at' => now()]);
        
        $product = ArchiveProduct::factory()->create(['quantity' => 1]);

        Livewire::test(Create::class)
            ->set('product_id', $product->id)
            ->call('selectProduct', $product->id)
            ->set('buyer_type', 'registered')
            ->set('user_id', $user->id)
            ->call('selectUser', $user->id)
            ->set('qty', 1)
            ->set('unit_price_inr', 1000)
            ->set('fulfillment_method', 'vault') // Try to force it
            ->call('store')
            ->assertHasErrors(['fulfillment_method']);

        $this->assertDatabaseMissing('orders', ['archive_product_id' => $product->id]);
    }
}
