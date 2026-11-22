<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Service> */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'service_category_id' => ServiceCategory::factory(),
            'name' => 'Test Service '.fake()->unique()->word(),
            'code' => null,
            'unit' => 'pcs',
            'default_price' => '10.00',
            'is_active' => true,
            'show_on_website' => true,
            'sort_order' => 0,
        ];
    }

    public function priced(string $price): static
    {
        return $this->state(fn () => ['default_price' => $price]);
    }
}
