<?php

namespace Sentience\Database\Queries\Enums;

use DateTime;

enum TypeEnum: string
{
    case Bool = 'bool';
    case Int = 'int';
    case Float = 'float';
    case String = 'string';
    case DateTime = DateTime::class;
}
