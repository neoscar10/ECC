<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContactConfig;
use App\Models\ContactSubject;

class ContactConfigSeeder extends Seeder
{
    public function run()
    {
        ContactConfig::firstOrCreate([], [
            'concierge_phone' => '+44 (0) 20 7123 4567',
            'support_email' => 'members@executivecricket.club',
        ]);

        $subjects = [
            ['label' => 'Membership Upgrade', 'sort_order' => 1],
            ['label' => 'Dining Reservations', 'sort_order' => 2],
            ['label' => 'General Feedback', 'sort_order' => 3],
            ['label' => 'Other', 'sort_order' => 4],
        ];

        foreach ($subjects as $subject) {
            ContactSubject::firstOrCreate(['label' => $subject['label']], $subject);
        }
    }
}
