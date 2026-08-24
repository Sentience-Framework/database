<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Enums\UnionEnum;
use Sentience\Database\Queries\SelectQuery;

class Union
{
    public function __construct(
        public UnionEnum $union,
        public SelectQuery $selectQuery
    ) {
    }
}
