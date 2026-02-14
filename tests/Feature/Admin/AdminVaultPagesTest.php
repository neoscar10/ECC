<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Vault\Index;
use App\Livewire\Admin\Vault\Show;
use App\Models\User;
use App\Models\UserVaultItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminVaultPagesTest extends TestCase
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
    public function admin_can_view_vault_index()
    {
        $this->get(route('admin.vault-access.index'))
            ->assertStatus(200);
            
        Livewire::test(Index::class)
            ->assertStatus(200);
    }

    /** @test */
    public function admin_can_view_vault_show_page()
    {
        $user = User::factory()->create();
        $item = UserVaultItem::factory()->create(['user_id' => $user->id]);

        $this->get(route('admin.vault-access.show', $user))
            ->assertStatus(200)
            ->assertSee($item->item_title);

        Livewire::test(Show::class, ['user' => $user])
            ->assertStatus(200)
            ->assertSee($item->item_title);
    }
}
