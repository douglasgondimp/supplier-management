<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $estado_id
 * @property string $nome
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['estado_id', 'nome'])]
class Cidade extends Model
{
    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class);
    }
}
