<?php

namespace Database\Factories;

use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductCategory> */
class ProductCategoryFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => 'Test Product Category '.fake()->unique()->word(), 'sort_order' => 0, 'is_active' => true];
    }
}
