<?php

namespace App\Rules;

use App\Models\SupplierIndividual;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class UniqueCPF implements ValidationRule
{
    public function __construct(private readonly ?string $originalDocument = null) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $document = preg_replace('/[^0-9]/', '', $value);

        if ($this->originalDocument !== null
            && $document === preg_replace('/[^0-9]/', '', $this->originalDocument)) {
            return;
        }

        if (SupplierIndividual::where('cpf', $document)->exists()) {
            $fail('O CPF já existe.');
        }
    }
}
