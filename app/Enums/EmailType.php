<?php

namespace App\Enums;

enum EmailType: string
{
    case PESSOAL = 'pessoal';
    case COMERCIAL = 'comercial';
    case OUTRO = 'outro';
}
