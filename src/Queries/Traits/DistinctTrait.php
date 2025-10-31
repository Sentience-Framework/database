<?php

namespace Sentience\Database\Queries\Traits;

trait DistinctTrait
{
    protected bool $distinct = false;

    public function distinct(): static
    {
        $this->distinct = true;

        return $this;
    }
}
