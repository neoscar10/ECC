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

        // Create Permissions
        $permissions = [
            'view_auctions',
            'bid_auctions',
            'view_archive',
            'manage_enquiries',
            'ecc_admin',
            'super_admin',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Roles and Assign Permissions
        $userRole = Role::firstOrCreate(['name' => 'user']);
        $userRole->syncPermissions(['view_auctions', 'bid_auctions', 'view_archive']);

        $adminRole = Role::firstOrCreate(['name' => 'ecc_admin']);
        $adminRole->syncPermissions(['view_auctions', 'bid_auctions', 'view_archive', 'manage_enquiries', 'ecc_admin']);

        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdminRole->syncPermissions(Permission::all());
    }
}
