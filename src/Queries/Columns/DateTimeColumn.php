<?php

namespace Sentience\Database\Queries\Columns;

use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Interfaces\Sql;
use Sentience\Database\Queries\Objects\Type;

class DateTimeColumn extends ColumnAbstract
{
    public function __construct(
        int $precision = 6,
        bool $notNull = false,
        null|float|Sql $default = null
    ) {
        parent::__construct($precision, $notNull, $default, false);
    }

    public function type(): string|Type
    {
        return new Type(TypeEnum::DateTime, $this->size);
    }
}
