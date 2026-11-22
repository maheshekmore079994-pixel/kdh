<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_category_id' => ProductCategory::factory(),
            'sku' => 'TST-'.fake()->unique()->numerify('#####'),
            'name' => 'Test Product '.fake()->word(),
            'unit' => 'pcs',
            'opening_stock' => '0.000',
            'current_stock' => '0.000',
            'reorder_level' => '0.000',
            'purchase_cost' => '2.00',
            'selling_price' => '5.00',
            'is_sellable' => true,
            'is_active' => true,
        ];
    }
}
