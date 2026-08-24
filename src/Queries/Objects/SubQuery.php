<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\SelectQuery;

class SubQuery
{
    public function __construct(
        public SelectQuery $selectQuery,
        public string $alias
    ) {
    }
}
