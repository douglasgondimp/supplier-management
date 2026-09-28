<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use LaravelLegends\PtBrValidator\Rules\Cpf as DocumentValidator;

class ValidCPF implements ValidationRule
{
    /** @param Closure(string, ?string=): PotentiallyTranslatedString $fail */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)
            || preg_match('~\A(?:[0-9]{11}|[0-9]{3}\.[0-9]{3}\.[0-9]{3}-[0-9]{2})\z~', $value) !== 1
            || ! (new DocumentValidator)->passes($attribute, $value)) {
            $fail('O CPF informado é inválido.');
        }
    }
}
