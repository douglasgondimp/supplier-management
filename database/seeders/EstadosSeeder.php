<?php

namespace Database\Seeders;

use App\Models\Estado;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class EstadosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $estados = json_decode(
            File::get(database_path('data/estados.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $data = collect($estados)
            ->map(fn(array $estados) => [
                'nome' => $estados['nome'],
                'sigla' => $estados['sigla'],
                'regiao' => $estados['regiao'],
            ])->all();

        Estado::upsert(
            $data,
            ['sigla'],
            ['nome', 'regiao']
        );
    }
}
