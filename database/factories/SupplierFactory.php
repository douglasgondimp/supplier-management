<?php

namespace Database\Factories;

use App\Enums\PersonType;
use App\Enums\PhoneType;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'active' => true,
            'type_person' => PersonType::P_FISICA,
            'phone_number' => fake('pt_BR')->cellphoneNumber(),
            'phone_type' => PhoneType::CELULAR,
            'zip_address' => fake('pt_BR')->postcode(),
            'street' => fake('pt_BR')->streetName(),
            'number' => (string) fake()->numberBetween(1, 9999),
            'complement' => null,
            'neighborhood' => fake('pt_BR')->citySuffix(),
            'city' => fake('pt_BR')->city(),
            'state' => fake('pt_BR')->stateAbbr(),
            'reference_point' => null,
            'has_condominium' => false,
            'condominium_address' => null,
            'condominium_number' => null,
            'note' => null,
        ];
    }

    public function individual(): static
    {
        return $this->state(fn(array $attributes) => [
            'type_person' => PersonType::P_FISICA,
        ]);
    }

    public function corporate(): static
    {
        return $this->state(fn(array $attributes) => [
            'type_person' => PersonType::P_JURIDICA,
        ]);
    }
}
