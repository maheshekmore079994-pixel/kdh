<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Supplier> */
class SupplierFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => 'Test Supplier '.fake()->company(), 'phone' => '99999'.fake()->numerify('#####'), 'is_active' => true];
    }
}
