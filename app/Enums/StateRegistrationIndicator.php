<?php

namespace App\Enums;

enum StateRegistrationIndicator: string
{
    case CONTRIBUINTE = 'contribuinte';
    case ISENTO = 'isento';
    case NAO_CONTRIBUINTE = 'nao_contribuinte';
}
