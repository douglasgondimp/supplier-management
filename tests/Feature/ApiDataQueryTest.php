<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ApiDataQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    /** @return array<string, array{string, string, string, string}> */
    public static function queries(): array
    {
        return [
            'CEP' => ['api-data.zip-code', 'cep', '01001000', 'https://viacep.com.br/ws/01001000/json/'],
            'CNPJ' => ['api-data.cnpj', 'cnpj', '11222333000181', 'https://receitaws.com.br/v1/cnpj/11222333000181'],
        ];
    }

    #[DataProvider('queries')]
    public function test_guests_cannot_query(string $route, string $field, string $value, string $url): void
    {
        $this->getJson(route($route, [$field => $value]))->assertUnauthorized();

        Http::assertNothingSent();
    }

    #[DataProvider('queries')]
    public function test_unverified_users_cannot_query(string $route, string $field, string $value, string $url): void
    {
        $this->actingAs(User::factory()->unverified()->make())
            ->getJson(route($route, [$field => $value]))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    #[DataProvider('queries')]
    public function test_missing_empty_or_non_string_parameters_are_rejected(string $route, string $field, string $value, string $url): void
    {
        $this->actingAs(User::factory()->make());

        foreach ([[], [$field => ''], [$field => ['invalid']]] as $parameters) {
            $this->getJson(route($route, $parameters))
                ->assertUnprocessable()
                ->assertJsonValidationErrors($field);
        }

        Http::assertNothingSent();
    }

    public function test_zip_code_returns_address_fields_for_the_form(): void
    {
        $url = 'https://viacep.com.br/ws/01001000/json/';
        Http::fake([$url => Http::response([
            'cep' => '01001-000',
            'logradouro' => 'Praça da Sé',
            'complemento' => 'lado ímpar',
            'bairro' => 'Sé',
            'localidade' => 'São Paulo',
            'uf' => 'SP',
        ])]);

        $this->actingAs(User::factory()->make())
            ->getJson(route('api-data.zip-code', ['cep' => '01001000']))
            ->assertOk()
            ->assertExactJson([
                'street' => 'Praça da Sé',
                'complement' => 'lado ímpar',
                'neighborhood' => 'Sé',
                'city' => 'São Paulo',
                'state' => 'SP',
            ]);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET' && $request->url() === $url);
        Http::assertSentCount(1);
    }

    public function test_cnpj_returns_company_fields_for_the_form(): void
    {
        $url = 'https://receitaws.com.br/v1/cnpj/11222333000181';
        Http::fake([$url => Http::response([
            'status' => 'OK',
            'nome' => 'Empresa Exemplo Ltda',
            'fantasia' => 'Empresa Exemplo',
            'situacao' => 'ATIVA',
        ])]);

        $this->actingAs(User::factory()->make())
            ->getJson(route('api-data.cnpj', ['cnpj' => '11222333000181']))
            ->assertOk()
            ->assertExactJson([
                'company_name' => 'Empresa Exemplo Ltda',
                'fantasy_name' => 'Empresa Exemplo',
                'cnpj_status' => 'ATIVA',
            ]);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET' && $request->url() === $url);
        Http::assertSentCount(1);
    }

    public function test_unknown_zip_code_returns_not_found(): void
    {
        Http::fake(['https://viacep.com.br/ws/00000000/json/' => Http::response(['erro' => true])]);

        $this->actingAs(User::factory()->make())
            ->getJson(route('api-data.zip-code', ['cep' => '00000000']))
            ->assertNotFound()
            ->assertExactJson(['message' => 'CEP não encontrado.']);
    }

    public function test_cnpj_provider_error_returns_not_found(): void
    {
        Http::fake(['https://receitaws.com.br/v1/cnpj/00000000000000' => Http::response([
            'status' => 'ERROR',
            'message' => 'CNPJ inválido',
        ])]);

        $this->actingAs(User::factory()->make())
            ->getJson(route('api-data.cnpj', ['cnpj' => '00000000000000']))
            ->assertNotFound()
            ->assertExactJson(['message' => 'CNPJ não encontrado.']);
    }

    #[DataProvider('queries')]
    public function test_http_errors_return_bad_gateway(string $route, string $field, string $value, string $url): void
    {
        $this->actingAs(User::factory()->make());

        foreach ([429, 500] as $status) {
            Http::fake([$url => Http::response('', $status)]);

            $this->getJson(route($route, [$field => $value]))
                ->assertStatus(502)
                ->assertExactJson(['message' => 'Não foi possível consultar o '.strtoupper($field).'. Tente novamente.']);
        }
    }

    #[DataProvider('queries')]
    public function test_invalid_provider_json_returns_bad_gateway(string $route, string $field, string $value, string $url): void
    {
        Http::fake([$url => Http::response('<html>Serviço indisponível</html>', 200)]);

        $this->actingAs(User::factory()->make())
            ->getJson(route($route, [$field => $value]))
            ->assertStatus(502)
            ->assertExactJson(['message' => 'Não foi possível consultar o '.strtoupper($field).'. Tente novamente.']);
    }

    #[DataProvider('queries')]
    public function test_connection_failures_return_bad_gateway(string $route, string $field, string $value, string $url): void
    {
        Http::fake([$url => Http::failedConnection()]);

        $this->actingAs(User::factory()->make())
            ->getJson(route($route, [$field => $value]))
            ->assertStatus(502)
            ->assertExactJson(['message' => 'Não foi possível consultar o '.strtoupper($field).'. Tente novamente.']);
    }
}
