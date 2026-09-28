<?php

namespace Tests\Feature;

use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Models\SupplierCorporate;
use App\Models\SupplierIndividual;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SupplierCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_suppliers(): void
    {
        $this->get('/suppliers')->assertRedirect('/login');
        $this->post('/suppliers', [])->assertRedirect('/login');
    }

    public function test_list_pagination_and_relationship_search(): void
    {
        $this->actingAs(User::factory()->create());
        SupplierIndividual::factory()->count(31)->create();
        $person = SupplierCorporate::factory()->create(['company_name' => 'Empresa Singular', 'fantasy_name' => 'Apelido Exclusivo', 'cnpj' => '12345678000199']);
        $this->get('/suppliers')->assertInertia(fn(Assert $page) => $page->component('suppliers/Index')->has('suppliers.data', 10)->where('suppliers.total', 32));
        foreach ([10, 20, 30, 50] as $size) {
            $this->get('/suppliers?per_page=' . $size)->assertInertia(fn(Assert $page) => $page->has('suppliers.data', min($size, 32))->where('suppliers.per_page', $size));
        }
        foreach (['Empresa Singular', 'Apelido Exclusivo', '12.345.678/0001-99'] as $search) {
            $this->get('/suppliers?search=' . urlencode($search))->assertInertia(fn(Assert $page) => $page->has('suppliers.data', 1)->where('suppliers.data.0.id', $person->supplier_id));
        }
        $individual = SupplierIndividual::factory()->create(['name' => 'Nome Único', 'surname' => 'Alcunha Única', 'cpf' => '98765432100']);
        foreach (['Nome Único', 'Alcunha Única', '987.654.321-00'] as $search) {
            $this->get('/suppliers?search=' . urlencode($search))->assertInertia(fn(Assert $page) => $page->has('suppliers.data', 1)->where('suppliers.data.0.id', $individual->supplier_id));
        }
        $this->get('/suppliers?per_page=15')->assertSessionHasErrors('per_page');
        $this->get('/suppliers?search=ausente')->assertInertia(fn(Assert $page) => $page->has('suppliers.data', 0));
    }

    public function test_crud_persists_relationships_and_soft_deletes(): void
    {
        $this->actingAs(User::factory()->create());
        $data = Supplier::factory()->raw();
        $data['type_person'] = 'fisica';
        $data['phone_type'] = $data['phone_type']->value;
        $data['individual'] = ['name' => 'Maria', 'surname' => 'Mari', 'cpf' => '529.982.247-25', 'document_number' => '12345'];
        $data['emails'] = [['email' => 'maria@example.com', 'email_type' => 'pessoal']];
        $data['phones'] = [['phone_number' => '(11) 99999-8888', 'phone_type' => 'celular']];
        $data['contacts'] = [['name' => 'Contato', 'phone_number' => '(11) 98888-7777', 'phone_type' => 'celular']];
        $this->get('/suppliers/create')->assertOk();
        $this->post('/suppliers', $data)->assertSessionHasNoErrors()->assertRedirect('/suppliers');
        $supplier = Supplier::firstOrFail();
        $this->assertSame('52998224725', $supplier->individual->cpf);
        $this->assertCount(1, $supplier->emails);
        $this->assertCount(1, $supplier->contacts);
        $this->assertSame('11999998888', $supplier->phones->first()->phone_number);
        $this->get('/suppliers/' . $supplier->id)->assertInertia(fn(Assert $page) => $page->component('suppliers/Form')->where('readonly', true)->has('supplier.contacts', 1));
        $this->get('/suppliers/' . $supplier->id . '/edit')->assertInertia(fn(Assert $page) => $page->where('readonly', false));
        $data['individual']['name'] = 'Maria Atualizada';
        $data['emails'] = [];
        $data['active'] = false;
        $this->put('/suppliers/' . $supplier->id, $data)->assertSessionHasNoErrors()->assertRedirect('/suppliers');
        $supplier->refresh();
        $this->assertNull($supplier->corporate);
        $this->assertSame('Maria Atualizada', $supplier->individual->name);
        $this->assertFalse($supplier->active);
        $this->assertCount(0, $supplier->emails);
        $this->delete('/suppliers/' . $supplier->id)->assertRedirect('/suppliers');
        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
        $this->get('/suppliers/' . $supplier->id)->assertNotFound();
        $this->get('/suppliers')->assertInertia(fn(Assert $page) => $page->has('suppliers.data', 0));
    }

    public function test_person_type_cannot_change_in_either_direction(): void
    {
        $this->actingAs(User::factory()->create());
        foreach ([SupplierIndividual::factory()->create()->supplier, SupplierCorporate::factory()->create()->supplier] as $supplier) {
            $original = $supplier->type_person;
            $data = Supplier::factory()->raw();
            $data['phone_type'] = $data['phone_type']->value;
            $data['emails'] = $data['phones'] = $data['contacts'] = [];
            $data['type_person'] = $original->value === 'fisica' ? 'juridica' : 'fisica';
            $data['individual'] = ['name' => 'Outro nome', 'cpf' => '529.982.247-25', 'document_number' => '12345'];
            $data['corporate'] = ['company_name' => 'Empresa', 'fantasy_name' => 'Fantasia', 'cnpj' => '11.222.333/0001-81', 'state_registration_indicator' => 'isento', 'remittance' => 'retido'];
            $before = $supplier->load(['individual', 'corporate'])->toArray();
            $this->put('/suppliers/' . $supplier->id, $data)->assertSessionHasErrors(['type_person' => 'O tipo de pessoa não pode ser alterado após o cadastro.']);
            $this->assertSame($before, $supplier->fresh()->load(['individual', 'corporate'])->toArray());
            $data['type_person'] = $original->value;
            $this->put('/suppliers/' . $supplier->id, $data)->assertSessionHasNoErrors()->assertRedirect('/suppliers');
            $this->assertSame($original, $supplier->fresh()->type_person);
        }
    }

    public function test_resource_returns_all_relationships_without_preloading(): void
    {
        $supplier = Supplier::factory()->create();
        $supplier->individual()->create(['name' => 'Nome', 'surname' => 'Apelido', 'cpf' => '52998224725', 'document_number' => '1234']);
        $supplier->emails()->create(['email' => 'test@example.com', 'email_type' => 'comercial']);
        $supplier->phones()->create(['phone_number' => '11999998888', 'phone_type' => 'celular']);
        $supplier->contacts()->create(['name' => 'Contato', 'company' => 'Empresa', 'position' => 'Compras', 'phone_number' => '11988887777', 'phone_type' => 'celular', 'email' => 'contact@example.com', 'email_type' => 'comercial']);
        $data = (new SupplierResource($supplier->fresh()))->resolve();
        $this->assertSame('1234', $data['individual']['document_number']);
        $this->assertNull($data['corporate']);
        $this->assertSame('test@example.com', $data['emails'][0]['email']);
        $this->assertSame('11999998888', $data['phones'][0]['phone_number']);
        $this->assertSame('Compras', $data['contacts'][0]['position']);
        $this->assertSame('contact@example.com', $data['contacts'][0]['email']);
    }

    public function test_optional_blank_rows_are_ignored_and_all_form_data_can_be_updated(): void
    {
        $this->actingAs(User::factory()->create());
        $data = Supplier::factory()->raw();
        $data['type_person'] = 'fisica';
        $data['phone_type'] = $data['phone_type']->value;
        $data['individual'] = ['name' => 'Maria', 'cpf' => '52998224725', 'document_number' => '1234'];
        $data['emails'] = [['email' => '', 'email_type' => 'pessoal'], ['email' => 'test@example.com', 'email_type' => 'comercial']];
        $data['phones'] = [['phone_number' => '', 'phone_type' => 'celular'], ['phone_number' => '(11) 99999-8888', 'phone_type' => 'celular']];
        $data['contacts'] = [];
        $data['note'] = '<p><b>Observação</b></p><ul><li>Item</li></ul>';
        $data['has_condominium'] = true;
        $data['condominium_address'] = 'Condomínio Central';
        $data['condominium_number'] = '12';
        $this->post('/suppliers', $data)->assertSessionHasNoErrors()->assertRedirect('/suppliers');
        $supplier = Supplier::firstOrFail();
        $this->assertCount(1, $supplier->emails);
        $this->assertCount(1, $supplier->phones);
        $this->get('/suppliers/' . $supplier->id . '/edit')->assertInertia(fn(Assert $page) => $page
            ->where('supplier.note', $data['note'])
            ->where('supplier.condominium_address', 'Condomínio Central')
            ->where('supplier.condominium_number', '12')
            ->has('supplier.emails', 1)->has('supplier.phones', 1));
        $data['emails'] = [['email' => '', 'email_type' => 'pessoal']];
        $data['phones'] = [];
        $this->put('/suppliers/' . $supplier->id, $data)->assertSessionHasNoErrors();
        $this->assertCount(0, $supplier->fresh()->emails);
        $this->assertCount(0, $supplier->fresh()->phones);
        $data['emails'] = [['email' => 'test@example.com', 'email_type' => '']];
        $data['phones'] = [['phone_number' => '(11) 99999-8888', 'phone_type' => '']];
        $this->put('/suppliers/' . $supplier->id, $data)->assertSessionHasErrors(['emails.0.email_type', 'phones.0.phone_type']);
    }

    public function test_invalid_data_does_not_create_supplier(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/suppliers', [
                'type_person' => 'fisica',
                'individual' => ['cpf' => 'abc'],
                'contacts' => 'invalid',
                'phones' => 'invalid'
            ])
            ->assertSessionHasErrors(['individual.cpf', 'individual.name', 'phone_number', 'contacts', 'phones']);
        $this->assertDatabaseCount('suppliers', 0);
    }

    public function test_cpf_and_phone_validation_on_create_and_update(): void
    {
        $this->actingAs(User::factory()->create());
        $data = Supplier::factory()->raw();
        $data['type_person'] = 'fisica';
        $data['phone_type'] = $data['phone_type']->value;
        $data['individual'] = ['name' => 'Maria', 'cpf' => '529.982.247-25', 'document_number' => '1234'];
        $data['phone_number'] = '(11) 3333-4444';
        $data['emails'] = [];
        $data['phones'] = [['phone_number' => '(11) 99999-8888', 'phone_type' => 'celular']];
        $data['contacts'] = [['name' => 'Contato', 'phone_number' => '(11) 98888-7777', 'phone_type' => 'celular']];

        $invalid = $data;
        $invalid['individual']['cpf'] = '529.982.247-24';
        $invalid['phone_number'] = '3333-4444';
        $invalid['phones'][0]['phone_number'] = '1199999888a';
        $invalid['contacts'][0]['phone_number'] = '119999988888';
        $errors = ['individual.cpf', 'phone_number', 'phones.0.phone_number', 'contacts.0.phone_number'];
        $this->post('/suppliers', $invalid)->assertSessionHasErrors($errors);
        $this->assertDatabaseCount('suppliers', 0);

        $this->post('/suppliers', $data)->assertSessionHasNoErrors();
        $supplier = Supplier::firstOrFail();
        $this->assertSame('1133334444', $supplier->phone_number);
        $this->assertSame('11988887777', $supplier->contacts->first()->phone_number);
        $this->put('/suppliers/' . $supplier->id, $invalid)->assertSessionHasErrors($errors);
        $this->assertSame('52998224725', $supplier->fresh()->individual->cpf);

        $data['individual']['cpf'] = '111.111.111-11';
        $this->put('/suppliers/' . $supplier->id, $data)->assertSessionHasErrors('individual.cpf');
    }

    public function test_duplicate_cpf_check_upon_creation_and_update(): void
    {
        $this->actingAs(User::factory()->create());
        $supplier1 = Supplier::factory()->raw();
        $supplier1['type_person'] = 'fisica';
        $supplier1['phone_type'] = $supplier1['phone_type']->value;
        $supplier1['individual'] = ['name' => 'Maria', 'cpf' => '529.982.247-25', 'document_number' => '1234'];
        $supplier1['phone_number'] = '(11) 93333-4444';
        $supplier1['emails'] = [];
        $supplier1['phones'] = [];
        $supplier1['contacts'] = [];
        $this->post('/suppliers', $supplier1)->assertSessionHasNoErrors()->assertRedirect('/suppliers');
        $supplierUpdate = Supplier::firstOrFail();

        $supplier2 = Supplier::factory()->raw();
        $supplier2['type_person'] = 'fisica';
        $supplier2['phone_type'] = $supplier2['phone_type']->value;
        $supplier2['individual'] = ['name' => 'João', 'cpf' => '029.246.220-42', 'document_number' => '1234'];
        $supplier2['phone_number'] = '(11) 95555-6666';
        $supplier2['emails'] = [];
        $supplier2['phones'] = [];
        $supplier2['contacts'] = [];
        $this->post('/suppliers', $supplier2)->assertSessionHasNoErrors()->assertRedirect('/suppliers');

        $supplier3 = Supplier::factory()->raw();
        $supplier3['type_person'] = 'fisica';
        $supplier3['phone_type'] = $supplier3['phone_type']->value;
        $supplier3['individual'] = ['name' => 'Pedro', 'cpf' => '029.246.220-42', 'document_number' => '1234'];
        $supplier3['phone_number'] = '(11) 95555-6666';
        $supplier3['emails'] = [];
        $supplier3['phones'] = [];
        $supplier3['contacts'] = [];
        $this->post('/suppliers', $supplier3)->assertSessionHasErrors(['individual.cpf']);

        $supplier1['individual']['cpf'] = '029.246.220-42';
        $this->put('/suppliers/' . $supplierUpdate->id, $supplier1)->assertSessionHasErrors(['individual.cpf']);
    }

    public function test_duplicate_cnpj_check_upon_creation_and_update(): void
    {
        $this->actingAs(User::factory()->create());
        $supplier1 = Supplier::factory()->raw();
        $supplier1['type_person'] = 'juridica';
        $supplier1['phone_type'] = $supplier1['phone_type']->value;
        $supplier1['corporate'] = ['company_name' => 'Empresa', 'fantasy_name' => 'Fantasia', 'cnpj' => '11.222.333/0001-81', 'state_registration_indicator' => 'isento', 'remittance' => 'retido'];
        $supplier1['phone_number'] = '(11) 93333-4444';
        $supplier1['emails'] = [];
        $supplier1['phones'] = [];
        $supplier1['contacts'] = [];
        $this->post('/suppliers', $supplier1)->assertSessionHasNoErrors()->assertRedirect('/suppliers');
        $supplierUpdate = Supplier::firstOrFail();

        $supplier2 = Supplier::factory()->raw();
        $supplier2['type_person'] = 'juridica';
        $supplier2['phone_type'] = $supplier2['phone_type']->value;
        $supplier2['corporate'] = ['company_name' => 'Empresa 2', 'fantasy_name' => 'Fantasia 2', 'cnpj' => '92.760.026/0001-56', 'state_registration_indicator' => 'isento', 'remittance' => 'retido'];
        $supplier2['phone_number'] = '(11) 95555-6666';
        $supplier2['emails'] = [];
        $supplier2['phones'] = [];
        $supplier2['contacts'] = [];
        $this->post('/suppliers', $supplier2)->assertSessionHasNoErrors()->assertRedirect('/suppliers');

        $supplier3 = Supplier::factory()->raw();
        $supplier3['type_person'] = 'juridica';
        $supplier3['phone_type'] = $supplier3['phone_type']->value;
        $supplier3['corporate'] = ['company_name' => 'Empresa 3', 'fantasy_name' => 'Fantasia 3', 'cnpj' => '92.760.026/0001-56', 'state_registration_indicator' => 'isento', 'remittance' => 'retido'];
        $supplier3['phone_number'] = '(11) 95555-6666';
        $supplier3['emails'] = [];
        $supplier3['phones'] = [];
        $supplier3['contacts'] = [];
        $this->post('/suppliers', $supplier3)->assertSessionHasErrors(['corporate.cnpj']);

        $supplier1['corporate']['cnpj'] = '92.760.026/0001-56';
        $this->put('/suppliers/' . $supplierUpdate->id, $supplier1)->assertSessionHasErrors(['corporate.cnpj']);
    }
}
