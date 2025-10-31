<?php

namespace Sentience\Database\Queries\Traits;

trait ValuesTrait
{
    protected array $values = [];

    public function values(array $values): static
    {
        $this->values = $values;

        return $this;
    }
}
