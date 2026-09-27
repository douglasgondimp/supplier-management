<?php

namespace Tests\Feature;

use App\Enums\PersonType;
use App\Models\Supplier;
use App\Models\SupplierContact;
use App\Models\SupplierCorporate;
use App\Models\SupplierEmail;
use App\Models\SupplierIndividual;
use App\Models\SupplierPhones;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_individual_factory_creates_a_supplier_and_uses_its_primary_key(): void
    {
        Supplier::factory()->create();
        $individual = SupplierIndividual::factory()->create();

        $this->assertSame(PersonType::P_FISICA, $individual->supplier->type_person);
        $this->assertSame($individual->supplier_id, $individual->getKey());
        $this->assertTrue($individual->supplier->individual->is($individual));
        $this->assertMatchesRegularExpression('/^\d{11}$/', $individual->cpf);
        $individual->update(['name' => 'Nome atualizado']);
        $this->assertSame('Nome atualizado', $individual->fresh()->name);
    }

    public function test_corporate_factory_creates_a_supplier_and_uses_its_primary_key(): void
    {
        Supplier::factory()->create();
        $corporate = SupplierCorporate::factory()->create();

        $this->assertSame(PersonType::P_JURIDICA, $corporate->supplier->type_person);
        $this->assertSame($corporate->supplier_id, $corporate->getKey());
        $this->assertTrue($corporate->supplier->corporate->is($corporate));
        $this->assertMatchesRegularExpression('/^\d{14}$/', $corporate->cnpj);
        $corporate->update(['company_name' => 'Empresa atualizada']);
        $this->assertSame('Empresa atualizada', $corporate->fresh()->company_name);
    }

    public function test_contact_factories_create_their_supplier(): void
    {
        foreach ([SupplierEmail::class, SupplierPhones::class, SupplierContact::class] as $model) {
            $record = $model::factory()->create();

            $this->assertNotNull($record->supplier, "A relação suppliers não foi encontrada em {$model}");
            $this->assertSame($record->supplier_id, $record->supplier->id);
            $this->assertTrue($record->fresh()->is($record));
        }
    }

    public function test_factories_can_share_a_supplier_through_relationships(): void
    {
        $supplier = Supplier::factory()
            ->corporate()
            ->has(SupplierCorporate::factory(), 'corporate')
            ->has(SupplierEmail::factory()->count(2), 'emails')
            ->has(SupplierPhones::factory()->count(2), 'phones')
            ->has(SupplierContact::factory()->count(2), 'contacts')
            ->create();

        $this->assertDatabaseCount('suppliers', 1);
        $this->assertSame($supplier->id, $supplier->corporate->supplier_id);
        foreach (['emails', 'phones', 'contacts'] as $relation) {
            $this->assertCount(2, $supplier->{$relation});
            $this->assertSame([$supplier->id], $supplier->{$relation}->pluck('supplier_id')->unique()->values()->all());
        }
    }
}
