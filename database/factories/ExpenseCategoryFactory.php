<?php

namespace Database\Factories;

use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ExpenseCategory> */
class ExpenseCategoryFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => 'Test Expense '.fake()->unique()->word(), 'is_active' => true, 'sort_order' => 0];
    }
}
