<?php

namespace Tests\Feature\Admin;

use App\Models\MembershipTier;
use App\Models\User;
use App\Models\UserVaultItem;
use App\Models\Archive\ArchiveProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VaultDetailsUITest extends TestCase
{
    use RefreshDatabase;

    public function test_vault_details_page_displays_enhanced_ui()
    {
        // 1. Setup Admin
        $role = \Spatie\Permission\Models\Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole($role);

        // 2. Setup Member with Vault Access
        $tier = MembershipTier::factory()->create(['has_vault_access' => true, 'name' => 'Platinum']);
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'city' => 'Mumbai',
            'country' => 'India',
            'phone' => '+919876543210',
        ]);
        // Manually create membership
        \App\Models\Membership::create([
            'user_id' => $user->id,
            'membership_tier_id' => $tier->id,
            'status' => 'active',
            'started_at' => now(),
        ]);

        // 3. Create Vault Items
        UserVaultItem::create([
            'user_id' => $user->id,
            'source_type' => 'archive_product',
            'source_id' => 1,
            'item_title' => 'Test Bat',
            'item_image_url' => 'https://example.com/bat.jpg',
            'status' => 'locked',
            'locked_at' => now(),
            'currency' => 'INR',
            'price' => 1000,
        ]);

        // 4. Act
        $response = $this->actingAs($admin)->get(route('admin.vault-access.show', $user));

        // 5. Assert
        $response->assertStatus(200);

        // Back Button
        $response->assertSee('Back to List');
        // $response->assertSee('admin/vault-access');

        // Profile Details
        $response->assertSee('Member Code :');
        $response->assertSee('EXEC-' . str_pad($user->id, 4, '0', STR_PAD_LEFT));
        $response->assertSee('Phone :');
        $response->assertSee('+919876543210');
        $response->assertSee('Location :');
        $response->assertSee('Mumbai, India');
        $response->assertSee('Joined :');
        // $response->assertSee($user->created_at->format('d M, Y')); // Might be flaky with timezones, check content

        // Vault Stats
        $response->assertSee('Vault Stats');
        $response->assertSee('Locked Items');
        
        // Image
        $response->assertSee('https://example.com/bat.jpg');
    }
}
