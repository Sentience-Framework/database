<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Interfaces\Sql;

class Alias
{
    public function __construct(
        public string|array|Sql $identifier,
        public string $alias
    ) {
    }
}
