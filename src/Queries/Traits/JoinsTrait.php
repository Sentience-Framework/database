<?php

namespace Sentience\Database\Queries\Traits;

use Sentience\Database\Queries\Enums\JoinEnum;
use Sentience\Database\Queries\Objects\Alias;
use Sentience\Database\Queries\Objects\Join;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Queries\Query;

trait JoinsTrait
{
    protected array $joins = [];

    public function leftJoin(string|array|Alias|Raw $table, callable $on): static
    {
        return $this->addJoin(JoinEnum::LEFT_JOIN, $table, $on);
    }

    public function innerJoin(string|array|Alias|Raw $table, callable $on): static
    {
        return $this->addJoin(JoinEnum::INNER_JOIN, $table, $on);
    }

    public function join(string $join): static
    {
        $this->joins[] = Query::raw($join);

        return $this;
    }

    protected function addJoin(JoinEnum $join, string|array|Alias|Raw $table, callable $on): static
    {
        $join = new Join($join, $table);

        $join = $on($join) ?? $join;

        if (!($join instanceof Join)) {
            return $this;
        }

        if (count($join->getConditions()) == 0) {
            return $this;
        }

        $this->joins[] = $join;

        return $this;
    }
}
