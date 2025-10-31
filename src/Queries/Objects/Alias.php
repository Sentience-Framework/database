<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\SelectQuery;

class Alias
{
    public function __construct(
        public string|array|Raw|SelectQuery $identifier,
        public string $alias
    ) {
    }
}
