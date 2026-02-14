<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Auctions\Orders\RecordSaleModal;
use App\Models\Auctions\AuctionLot;
use App\Models\MembershipTier;
use App\Models\Order;
use App\Models\User;
use App\Models\UserVaultItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RecordSaleVaultIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();
    }


    public function test_can_record_sale_delivered_to_vault_for_eligible_user()
    {
        // 1. Setup Admin and User with Vault Access
        // Create role if not exists (refresh db should clear it but factory might use it)
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole($role);
        
        $tier = MembershipTier::factory()->create(['has_vault_access' => true]);
        $user = User::factory()->create();
        $this->assignTier($user, $tier);

        // 2. Setup Auction Lot
        $lot = AuctionLot::factory()->create(['title' => 'Test Lot for Vault']);

        // 3. Test Livewire Component
        file_put_contents('C:\Users\USER\Desktop\projects\Executive Cricket Club\test_progress.txt', "Starting test...\n");
        try {
            Livewire::actingAs($admin)
                ->test(RecordSaleModal::class)
                ->call('open', $lot->id)
                ->set('unit_price_inr', 1000)
                ->call('selectUser', $user->id)
                ->assertSet('can_vault', true)
                ->set('fulfillment_method', 'vault')
                ->set('payment_method', 'Offline')
                ->call('store');
        } catch (\Throwable $e) {
            file_put_contents('C:\Users\USER\Desktop\projects\Executive Cricket Club\test_error_log.txt', "EXCEPTION CAUGHT:\n" . $e->getMessage() . "\n" . $e->getTraceAsString());
            throw $e;
        }


        // 4. Assertions
        $this->assertDatabaseHas('orders', [
            'auction_lot_id' => $lot->id,
            'user_id' => $user->id,
            'fulfillment_method' => 'vault',
        ]);

        $this->assertDatabaseHas('user_vault_items', [
            'user_id' => $user->id,
            'source_type' => 'auction_lot',
            'source_id' => $lot->id,
            'status' => 'locked',
        ]);
    }

    public function test_cannot_record_sale_delivered_to_vault_for_ineligible_user()
    {
        // 1. Setup Admin and User WITHOUT Vault Access
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole($role);
        
        $tier = MembershipTier::factory()->create(['has_vault_access' => false]);
        $user = User::factory()->create();
        $this->assignTier($user, $tier);

        // 2. Setup Auction Lot
        $lot = AuctionLot::factory()->create(['title' => 'Test Lot No Vault']);

        // 3. Test Livewire Component
        Livewire::actingAs($admin)
            ->test(RecordSaleModal::class)
            ->call('open', $lot->id)
            ->set('unit_price_inr', 1000)
            ->call('selectUser', $user->id)
            ->assertSet('can_vault', false)
            ->set('fulfillment_method', 'vault') // Force it
            ->set('payment_method', 'Offline')
            ->call('store')
            ->assertHasErrors(['fulfillment_method']);

        // 4. Assertions
        $this->assertDatabaseMissing('orders', [
            'auction_lot_id' => $lot->id,
        ]);

        $this->assertDatabaseMissing('user_vault_items', [
            'user_id' => $user->id,
            'source_id' => $lot->id,
        ]);
    }

    protected function assignTier($user, $tier)
    {
        $user->memberships()->create([
            'membership_tier_id' => $tier->id,
            'started_at' => now(),
            'expires_at' => now()->addYear(),
            'status' => 'active',
            'payment_status' => 'paid',
        ]);
    }
}
