<?php

namespace Database\Factories;

use App\Models\MembershipTier;
use Illuminate\Database\Eloquent\Factories\Factory;

class MembershipTierFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MembershipTier::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word . ' Tier',
            'code' => strtoupper($this->faker->unique()->lexify('????')),
            'price' => $this->faker->numberBetween(10, 1000),
            'duration_days' => 365,
            'is_active' => true,
            'benefits_json' => [],
        ];
    }
}
