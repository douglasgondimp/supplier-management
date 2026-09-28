<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ApiDataQueryService
{
    public function zipCode(string $cep): Response
    {
        return Http::connectTimeout(5)->timeout(10)->get("https://viacep.com.br/ws/{$cep}/json/");
    }

    public function cnpj(string $cnpj): Response
    {
        return Http::connectTimeout(5)->timeout(10)->get("https://receitaws.com.br/v1/cnpj/{$cnpj}");
    }
}
