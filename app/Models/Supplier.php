<?php

namespace App\Models;

use App\Enums\PersonType;
use App\Enums\PhoneType;
use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property bool $active
 * @property PersonType $type_person
 * @property string $phone_number
 * @property PhoneType $phone_type
 * @property string $zip_address
 * @property string $street
 * @property string $number
 * @property string|null $complement
 * @property string $neighborhood
 * @property string $city
 * @property string $state
 * @property string|null $reference_point
 * @property bool $has_condominium
 * @property string|null $condominium_address
 * @property string|null $condominium_number
 * @property string|null $note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['active', 'type_person', 'phone_number', 'phone_type', 'zip_address', 'street', 'number', 'complement', 'neighborhood', 'city', 'state', 'reference_point', 'has_condominium', 'condominium_address', 'condominium_number', 'note'])]
class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'type_person' => PersonType::class,
            'phone_type' => PhoneType::class,
            'has_condominium' => 'boolean',
        ];
    }

    public function individual(): HasOne
    {
        return $this->hasOne(SupplierIndividual::class, 'supplier_id');
    }

    public function corporate(): HasOne
    {
        return $this->hasOne(SupplierCorporate::class, 'supplier_id');
    }

    public function emails(): HasMany
    {
        return $this->hasMany(SupplierEmail::class, 'supplier_id');
    }

    public function phones(): HasMany
    {
        return $this->hasMany(SupplierPhones::class, 'supplier_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class, 'supplier_id');
    }
}
