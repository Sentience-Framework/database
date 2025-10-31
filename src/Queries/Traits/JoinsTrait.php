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

    public function leftJoin(string|array|Alias|Raw $joinTable, string $joinTableColumn, string|array|Raw $onTable, string $onTableColumn): static
    {
        $this->addJoin(JoinEnum::LEFT_JOIN, $joinTable, $joinTableColumn, $onTable, $onTableColumn);

        return $this;
    }

    public function innerJoin(string|array|Alias|Raw $joinTable, string $joinTableColumn, string|array|Raw $onTable, string $onTableColumn): static
    {
        $this->addJoin(JoinEnum::INNER_JOIN, $joinTable, $joinTableColumn, $onTable, $onTableColumn);

        return $this;
    }

    public function join(string $join): static
    {
        $this->joins[] = Query::raw($join);

        return $this;
    }

    protected function addJoin(JoinEnum $join, string|array|Alias|Raw $joinTable, string $joinTableColumn, string|array|Raw $onTable, string $onTableColumn): void
    {
        $this->joins[] = new Join($join, $joinTable, $joinTableColumn, $onTable, $onTableColumn);
    }
}
