<?php

namespace Sentience\Database\Queries\Traits;

trait GroupByTrait
{
    protected array $groupBy = [];

    public function groupBy(array $columns): static
    {
        $this->groupBy = $columns;

        return $this;
    }
}
