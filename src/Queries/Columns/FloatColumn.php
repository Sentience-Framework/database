<?php

namespace Sentience\Database\Queries\Columns;

use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Interfaces\Sql;
use Sentience\Database\Queries\Objects\Type;

class FloatColumn extends ColumnAbstract
{
    public function __construct(
        int $bits = 64,
        bool $notNull = false,
        null|float|Sql $default = null
    ) {
        parent::__construct($bits, $notNull, $default, false);
    }

    public function type(): string|Type
    {
        return new Type(TypeEnum::Float, $this->size);
    }
}
