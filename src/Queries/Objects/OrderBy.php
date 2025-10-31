<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Enums\OrderByDirectionEnum;

class OrderBy
{
    public function __construct(
        public string|array|Raw $column,
        public OrderByDirectionEnum $direction
    ) {
    }
}
