<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Roles (Only Admin + User, NO Tiers)
        $roles = [
            'user',
            'ecc_admin',
            'super_admin',
        ];

        // Ensure roles exist for guard 'web' as requested
        foreach ($roles as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }
    }
}
