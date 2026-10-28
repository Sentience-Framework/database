<?php

namespace Sentience\Database\Queries\Columns;

use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Interfaces\Sql;
use Sentience\Database\Queries\Objects\Type;

class StringColumn extends ColumnAbstract
{
    public function __construct(
        int $length = 255,
        bool $notNull = false,
        null|float|Sql $default = null
    ) {
        parent::__construct($length, $notNull, $default, false);
    }

    public function type(): string|Type
    {
        return new Type(TypeEnum::String, $this->size);
    }
}
