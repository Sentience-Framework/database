<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Enums\JoinEnum;

class Join
{
    public function __construct(
        public JoinEnum $join,
        public string|array|Alias|Raw $joinTable,
        public string $joinTableColumn,
        public string|array|Raw $onTable,
        public string $onTableColumn
    ) {
    }
}
