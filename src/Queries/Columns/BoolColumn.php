<?php

namespace Sentience\Database\Queries\Columns;

use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Interfaces\Sql;
use Sentience\Database\Queries\Objects\Type;

class BoolColumn extends ColumnAbstract
{
    public function __construct(
        bool $notNull = false,
        null|bool|Sql $default = null
    ) {
        parent::__construct(null, $notNull, $default, false);
    }

    public function type(): string|Type
    {
        return new Type(TypeEnum::Bool);
    }
}
