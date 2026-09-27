<?php

namespace App\Models;

use App\Enums\EmailType;
use Database\Factories\SupplierEmailFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $supplier_id
 * @property string $email
 * @property EmailType $email_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['supplier_id', 'email', 'email_type'])]
class SupplierEmail extends Model
{
    /** @use HasFactory<SupplierEmailFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'email_type' => EmailType::class,
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
