<?php

namespace App\Models;

use Database\Factories\SupplierIndividualFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $supplier_id
 * @property string $cpf
 * @property string $name
 * @property string|null $surname
 * @property string $document_number
 */
#[Fillable(['supplier_id', 'cpf', 'name', 'surname', 'document_number'])]
class SupplierIndividual extends Model
{
    /** @use HasFactory<SupplierIndividualFactory> */
    use HasFactory;

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'supplier_id';

    protected function cpf(): Attribute
    {
        return Attribute::make(
            set: fn(string $value) => preg_replace('/[^0-9]/', '', $value)
        );
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
