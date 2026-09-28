<?php

namespace Database\Factories;

use App\Enums\Remittance;
use App\Enums\StateRegistrationIndicator;
use App\Models\Supplier;
use App\Models\SupplierCorporate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierCorporate>
 */
class SupplierCorporateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory()->corporate(),
            'cnpj' => fake('pt_BR')->cnpj(),
            'company_name' => fake('pt_BR')->company(),
            'fantasy_name' => fake('pt_BR')->company(),
            'state_registration_indicator' => StateRegistrationIndicator::CONTRIBUINTE,
            'state_registration' => fake()->numerify('############'),
            'municipal_registration' => fake()->numerify('########'),
            'cnpj_status' => 'ATIVA',
            'remittance' => fake()->randomElement(Remittance::cases()),
        ];
    }
}
