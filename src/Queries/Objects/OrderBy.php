<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Enums\OrderByDirectionEnum;
use Sentience\Database\Queries\Interfaces\Sql;

class OrderBy
{
    public function __construct(
        public string|array|Sql $column,
        public OrderByDirectionEnum $direction
    ) {
    }
}
