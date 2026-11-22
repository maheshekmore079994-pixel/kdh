<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PaymentMethod> */
class PaymentMethodFactory extends Factory
{
    public function definition(): array
    {
        $code = fake()->unique()->lexify('method-????');

        return ['name' => 'Test '.$code, 'code' => $code, 'type' => 'other', 'is_active' => true, 'sort_order' => 0];
    }

    public function cash(): static
    {
        return $this->state(fn () => ['name' => 'Cash', 'code' => 'cash', 'type' => 'cash']);
    }

    public function upi(): static
    {
        return $this->state(fn () => ['name' => 'UPI', 'code' => 'upi', 'type' => 'upi']);
    }
}
