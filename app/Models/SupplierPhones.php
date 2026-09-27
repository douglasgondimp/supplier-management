<?php

namespace App\Models;

use App\Enums\PhoneType;
use Database\Factories\SupplierPhonesFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $supplier_id
 * @property string $phone_number
 * @property PhoneType $phone_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['supplier_id', 'phone_number', 'phone_type'])]
class SupplierPhones extends Model
{
    /** @use HasFactory<SupplierPhonesFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'phone_type' => PhoneType::class,
        ];
    }

    protected function phoneNumber(): Attribute
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
