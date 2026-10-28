<?php

namespace Sentience\Database\Queries\Columns;

use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Interfaces\Sql;
use Sentience\Database\Queries\Objects\Type;

class IntColumn extends ColumnAbstract
{
    public function __construct(
        int $bits = 64,
        bool $notNull = false,
        null|int|Sql $default = null,
        bool $generatedByDefaultAsIdentity = false
    ) {
        parent::__construct($bits, $notNull, $default, $generatedByDefaultAsIdentity);
    }

    public function type(): string|Type
    {
        return new Type(TypeEnum::Int, $this->size);
    }
}
