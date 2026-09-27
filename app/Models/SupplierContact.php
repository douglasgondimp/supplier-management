<?php

namespace App\Models;

use App\Enums\EmailType;
use Database\Factories\SupplierContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $supplier_id
 * @property int $name
 * @property int|null $company
 * @property int|null $position
 * @property int $phone_number
 * @property int $phone_type
 * @property int|null $email
 * @property EmailType|null $email_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['supplier_id', 'name', 'company', 'position', 'phone_number', 'phone_type', 'email', 'email_type'])]
class SupplierContact extends Model
{
    /** @use HasFactory<SupplierContactFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'email_type' => EmailType::class,
        ];
    }

    public function suppliers(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
