<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Customer> */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Test Customer '.fake()->lastName(),
            'mobile' => '99999'.fake()->unique()->numerify('#####'),
            'email' => null,
            'address' => null,
            'notes' => null,
        ];
    }
}
