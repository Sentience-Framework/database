<?php

namespace Sentience\Database\Queries\Traits;

trait DistinctTrait
{
    protected ?array $distinct = null;

    public function distinct($on = []): static
    {
        $this->distinct = $on;

        return $this;
    }
}
