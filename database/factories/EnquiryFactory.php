<?php

namespace Database\Factories;

use App\Models\Enquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Enquiry> */
class EnquiryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Test Enquirer',
            'phone' => '99999'.fake()->numerify('#####'),
            'email' => null,
            'service_interest' => null,
            'message' => 'Test enquiry message.',
            'status' => 'new',
        ];
    }
}
