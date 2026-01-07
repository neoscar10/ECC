<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MembershipTiersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Basic
        $basic = \App\Models\MembershipTier::updateOrCreate(['code' => 'basic'], [
            'name' => 'Basic',
            'level' => 0,
            'price_amount' => 0,
            'currency' => 'INR',
            'sort_order' => 0,
            'is_active' => true,
            'requires_approval' => false,
        ]);
        $this->addFeatures($basic, ['Access to news', 'Basic community access']);
        
        // Silver
        $silver = \App\Models\MembershipTier::updateOrCreate(['code' => 'silver'], [
            'name' => 'Silver',
            'level' => 1,
            'price_amount' => 500000, 
            'currency' => 'INR',
            'sort_order' => 1,
            'is_active' => true,
            'upgrade_from_id' => $basic->id,
            'requires_approval' => true,
        ]);
        $this->addFeatures($silver, ['Private Viewing', 'Quarterly Events']);
        $this->attachPrivileges($silver, ['private_viewing', 'lounge_entry']);

        // Gold
        $gold = \App\Models\MembershipTier::updateOrCreate(['code' => 'gold'], [
            'name' => 'Gold',
            'level' => 2,
            'price_amount' => 1500000,
            'currency' => 'INR',
            'sort_order' => 2,
            'is_active' => true,
            'upgrade_from_id' => $silver->id,
            'requires_approval' => true,
        ]);
        $this->addFeatures($gold, ['Priority Bidding', 'Monthly Galas', 'Concierge']);
        $this->attachPrivileges($gold, ['private_viewing', 'lounge_entry', 'priority_bidding']);

        // Sovereign
        $sovereign = \App\Models\MembershipTier::updateOrCreate(['code' => 'sovereign'], [
             'name' => 'Sovereign',
             'level' => 3,
             'price_amount' => 5000000,
             'currency' => 'INR',
             'sort_order' => 3,
             'is_active' => true,
             'upgrade_from_id' => $gold->id,
             'requires_approval' => true,
        ]);
        $this->addFeatures($sovereign, ['Vault Access', 'Personal Curator', 'Global Events']);
        $this->attachPrivileges($sovereign, ['private_viewing', 'lounge_entry', 'priority_bidding', 'vault_access']);
    }

    private function addFeatures($tier, $features) {
         $tier->features()->delete();
         foreach($features as $idx => $f) {
             $tier->features()->create(['feature' => $f, 'sort_order' => $idx]);
         }
    }
    
    private function attachPrivileges($tier, $keys) {
         $ids = \App\Models\Privilege::whereIn('key', $keys)->pluck('id');
         $tier->privileges()->sync($ids);
    }
}
