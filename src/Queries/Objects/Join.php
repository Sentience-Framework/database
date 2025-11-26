<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Enums\JoinEnum;
use Sentience\Database\Queries\Query;
use Sentience\Database\Queries\Traits\WhereTrait;

class Join
{
    use WhereTrait;

    public function __construct(
        public JoinEnum $join,
        public string|array|Alias|Raw $table
    ) {
    }

    public function on(array $join, array $on): static
    {
        return $this->whereEquals($join, Query::identifier($on));
    }

    public function orOn(array $join, array $on): static
    {
        return $this->orWhereEquals($join, Query::identifier($on));
    }

    public function getConditions(): array
    {
        return $this->where;
    }
}
