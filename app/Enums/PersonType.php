<?php

namespace App\Enums;

enum PersonType: string
{
    case P_FISICA = 'fisica';
    case P_JURIDICA = 'juridica';

    public function label(): string
    {
        return match ($this) {
            self::P_FISICA => 'Pessoa Física',
            self::P_JURIDICA => 'Pessoa Jurídica'
        };
    }
}
