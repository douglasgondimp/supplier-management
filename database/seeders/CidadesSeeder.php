<?php

namespace Database\Seeders;

use App\Models\Cidade;
use App\Models\Estado;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CidadesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cidades = json_decode(
            File::get(database_path('data/cidades.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $estados = Estado::pluck('id', 'sigla');

        $data = collect($cidades)
            ->map(fn(array $cidade) => [
                'estado_id' => $estados[$cidade['uf']],
                'nome' => $cidade['nome'],
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->all();

        Cidade::upsert(
            $data,
            ['estado_id', 'nome'],
            ['updated_at']
        );
    }
}
