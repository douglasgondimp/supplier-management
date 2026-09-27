<?php

namespace Database\Factories;

use App\Enums\PhoneType;
use App\Models\Supplier;
use App\Models\SupplierPhones;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierPhones>
 */
class SupplierPhonesFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'phone_number' => fake()->numerify('119########'),
            'phone_type' => PhoneType::CELULAR,
        ];
    }
}
