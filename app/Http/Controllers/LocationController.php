<?php

namespace App\Http\Controllers;

use App\Models\Estado;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    public function cidades(Estado $estado): JsonResponse
    {
        return response()->json($estado->cidades()->orderBy('nome')->get(['id', 'nome']));
    }
}
