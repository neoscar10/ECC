<?php

namespace Database\Factories;

use App\Models\ContactEnquiry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactEnquiryFactory extends Factory
{
    protected $model = ContactEnquiry::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'contact_name' => $this->faker->name,
            'contact_email' => $this->faker->safeEmail,
            'contact_phone' => $this->faker->phoneNumber,
            'subject' => $this->faker->randomElement(['membership_upgrade', 'dining_reservations', 'general_feedback', 'other']),
            'message' => $this->faker->paragraph,
            'status' => 'new', // Default status
        ];
    }
}
