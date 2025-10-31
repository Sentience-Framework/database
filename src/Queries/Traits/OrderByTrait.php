<?php

namespace Sentience\Database\Queries\Traits;

use Sentience\Database\Queries\Enums\OrderByDirectionEnum;
use Sentience\Database\Queries\Objects\OrderBy;
use Sentience\Database\Queries\Objects\Raw;

trait OrderByTrait
{
    protected array $orderBy = [];

    public function orderByAsc(string|array|Raw $column): static
    {
        $this->addOrderBy($column, OrderByDirectionEnum::ASC);

        return $this;
    }

    public function orderByDesc(string|array|Raw $column): static
    {
        $this->addOrderBy($column, OrderByDirectionEnum::DESC);

        return $this;
    }

    protected function addOrderBy(string|array|Raw $column, OrderByDirectionEnum $direction): void
    {
        $this->orderBy[] = new OrderBy($column, $direction);
    }
}
