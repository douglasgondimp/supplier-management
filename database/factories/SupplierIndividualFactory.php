<?php

namespace Database\Factories;

use App\Models\Supplier;
use App\Models\SupplierIndividual;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierIndividual>
 */
class SupplierIndividualFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory()->individual(),
            'cpf' => fake('pt_BR')->cpf(),
            'name' => fake('pt_BR')->name(),
            'surname' => fake('pt_BR')->lastName(),
            'document_number' => fake()->numerify('#########'),
        ];
    }
}
