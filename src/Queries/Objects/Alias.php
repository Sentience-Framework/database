<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Interfaces\Sql;
use Sentience\Database\Queries\SelectQuery;

class Alias
{
    public function __construct(
        public string|array|SelectQuery|Sql $identifier,
        public string $alias
    ) {
    }
}
