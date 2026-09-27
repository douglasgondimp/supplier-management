<?php

namespace Database\Factories;

use App\Enums\EmailType;
use App\Enums\PhoneType;
use App\Models\Supplier;
use App\Models\SupplierContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierContact>
 */
class SupplierContactFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'name' => fake('pt_BR')->name(),
            'company' => fake('pt_BR')->company(),
            'position' => fake()->randomElement(['Gerente', 'Diretor', 'Vendedor', 'Representante']),
            'phone_number' => fake()->numerify('119########'),
            'phone_type' => PhoneType::CELULAR->value,
            'email' => fake()->safeEmail(),
            'email_type' => fake()->randomElement(EmailType::cases()),
        ];
    }
}
