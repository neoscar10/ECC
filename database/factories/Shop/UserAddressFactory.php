<?php

namespace Database\Factories\Shop;

use App\Models\Shop\UserAddress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserAddressFactory extends Factory
{
    protected $model = UserAddress::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'label' => $this->faker->word,
            'full_name' => $this->faker->name,
            'phone' => $this->faker->phoneNumber,
            'line1' => $this->faker->streetAddress,
            'line2' => $this->faker->secondaryAddress,
            'city' => $this->faker->city,
            'state' => $this->faker->state,
            'postal_code' => $this->faker->postcode,
            'country' => $this->faker->country,
            'is_default' => false,
            'type' => 'shipping',
        ];
    }
}
