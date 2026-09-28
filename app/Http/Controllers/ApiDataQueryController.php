<?php

namespace App\Http\Controllers;

use App\Services\ApiDataQueryService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiDataQueryController extends Controller
{
    public function __construct(
        protected ApiDataQueryService $service
    ) {}

    public function zipCode(Request $request): JsonResponse
    {
        $request->validate(['cep' => ['required', 'string']]);

        try {
            $response = $this->service->zipCode($request->string('cep')->toString());
        } catch (ConnectionException) {
            return response()->json(['message' => 'Não foi possível consultar o CEP. Tente novamente.'], 502);
        }

        if (! $response->successful() || ! is_array($response->json())) {
            return response()->json(['message' => 'Não foi possível consultar o CEP. Tente novamente.'], 502);
        }

        if ($response->json('erro')) {
            return response()->json(['message' => 'CEP não encontrado.'], 404);
        }

        return response()->json([
            'street' => $response->json('logradouro', ''),
            'complement' => $response->json('complemento', ''),
            'neighborhood' => $response->json('bairro', ''),
            'city' => $response->json('localidade', ''),
            'state' => $response->json('uf', ''),
        ]);
    }

    public function cnpj(Request $request): JsonResponse
    {
        $request->validate(['cnpj' => ['required', 'string']]);

        try {
            $response = $this->service->cnpj($request->string('cnpj')->toString());
        } catch (ConnectionException) {
            return response()->json(['message' => 'Não foi possível consultar o CNPJ. Tente novamente.'], 502);
        }

        if (! $response->successful() || ! is_array($response->json())) {
            return response()->json(['message' => 'Não foi possível consultar o CNPJ. Tente novamente.'], 502);
        }

        if ($response->json('status') === 'ERROR' || $response->json('erro')) {
            return response()->json(['message' => 'CNPJ não encontrado.'], 404);
        }

        return response()->json([
            'company_name' => $response->json('nome') ?? '',
            'fantasy_name' => $response->json('fantasia') ?? '',
            'cnpj_status' => $response->json('situacao') ?? '',
        ]);
    }
}
