<?php

namespace Sentience\Database\Queries\Traits;

trait LimitTrait
{
    protected ?int $limit = null;

    public function limit(int $limit): static
    {
        $this->limit = $limit >= 0 ? $limit : null;

        return $this;
    }
}
