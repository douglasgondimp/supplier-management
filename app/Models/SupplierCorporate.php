<?php

namespace App\Models;

use App\Enums\Remittance;
use App\Enums\StateRegistrationIndicator;
use Database\Factories\SupplierCorporateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $supplier_id
 * @property string $cnpj
 * @property string $company_name
 * @property string $fantasy_name
 * @property StateRegistrationIndicator $state_registration_indicator
 * @property string|null $state_registration
 * @property string|null $municipal_registration
 * @property string $cnpj_status
 * @property Remittance $remittance
 */
#[Fillable(['supplier_id', 'cnpj', 'company_name', 'fantasy_name', 'state_registration_indicator', 'state_registration', 'municipal_registration', 'cnpj_status', 'remittance'])]
class SupplierCorporate extends Model
{
    /** @use HasFactory<SupplierCorporateFactory> */
    use HasFactory;

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'supplier_id';

    protected function casts(): array
    {
        return [
            'state_registration_indicator' => StateRegistrationIndicator::class,
            'remittance' => Remittance::class,
        ];
    }

    protected function cnpj(): Attribute
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
