<?php

namespace Sentience\Database\Queries\Traits;

use Sentience\Database\Queries\Enums\UnionEnum;
use Sentience\Database\Queries\Objects\Union;
use Sentience\Database\Queries\SelectQuery;

trait UnionTrait
{
    protected array $unions = [];

    public function union(SelectQuery $selectQuery): static
    {
        $this->unions[] = new Union(UnionEnum::UNION, $selectQuery);

        return $this;
    }

    public function unionAll(SelectQuery $selectQuery): static
    {
        $this->unions[] = new Union(UnionEnum::UNION_ALL, $selectQuery);

        return $this;
    }
}
