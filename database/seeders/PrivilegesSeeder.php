<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PrivilegesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $privileges = [
            ['key' => 'private_viewing', 'name' => 'Private Viewing', 'description' => 'Unlimited museum entry', 'sort_order' => 1],
            ['key' => 'priority_bidding', 'name' => 'Priority Bidding', 'description' => 'Early auction access', 'sort_order' => 2],
            ['key' => 'lounge_entry', 'name' => 'Lounge Entry', 'description' => 'Global club access', 'sort_order' => 3],
            ['key' => 'vault_access', 'name' => 'Vault Access', 'description' => 'Secure asset storage', 'sort_order' => 4],
        ];

        foreach ($privileges as $p) {
            \App\Models\Privilege::firstOrCreate(['key' => $p['key']], $p);
        }
    }
}
