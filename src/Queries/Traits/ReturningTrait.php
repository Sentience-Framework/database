<?php

namespace Sentience\Database\Queries\Traits;

trait ReturningTrait
{
    protected ?array $returning = null;

    public function returning(array $columns = []): static
    {
        $this->returning = $columns;

        return $this;
    }
}
