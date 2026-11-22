<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ServiceVariant> */
class ServiceVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'name' => 'Test Variant '.fake()->unique()->word(),
            'unit_price' => '5.00',
            'min_quantity' => '1.000',
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
