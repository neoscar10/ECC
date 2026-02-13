<?php

namespace Database\Factories;

use App\Domain\Membership\MembershipApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MembershipApplicationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MembershipApplication::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => 'draft',
            'current_step' => 'personal_details',
            'personal_details_json' => [],
            'cricket_profile_json' => [],
            'collector_intent_json' => [],
            'payment_status' => 'unpaid',
            'submitted_at' => null,
            'reviewed_at' => null,
        ];
    }
}
