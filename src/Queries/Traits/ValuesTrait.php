<?php

namespace Sentience\Database\Queries\Traits;

trait ValuesTrait
{
    protected array $values = [];

    public function values(array ...$values): static
    {
        array_push($this->values, ...$values);

        return $this;
    }
}
