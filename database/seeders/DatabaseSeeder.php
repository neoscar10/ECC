<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PrivilegesSeeder::class,
            MembershipTiersSeeder::class,
        ]);

        $admin = User::firstOrCreate([
            'email' => 'admin@ecc.com',
        ], [
            'name' => 'Super Admin',
            'password' => bcrypt('password'),
        ]);
        
        // Assign role if not already assigned
        if (!$admin->hasRole('super_admin')) {
             $admin->assignRole('super_admin');
        }
    }
}
