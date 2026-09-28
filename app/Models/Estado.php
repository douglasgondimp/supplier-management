<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $nome
 * @property string $sigla
 * @property string $regiao
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['nome', 'sigla', 'regiao'])]
class Estado extends Model
{
    public function cidades(): HasMany
    {
        return $this->hasMany(Cidades::class, 'estado_id');
    }
}
