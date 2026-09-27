<?php

namespace Database\Factories;

use App\Enums\EmailType;
use App\Models\Supplier;
use App\Models\SupplierEmail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierEmail>
 */
class SupplierEmailFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'email' => fake()->safeEmail(),
            'email_type' => fake()->randomElement(EmailType::cases()),
        ];
    }
}
