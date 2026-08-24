<?php

namespace Sentience\Database\Queries\Traits;

trait PrimaryKeysTrait
{
    protected array $primaryKeys = [];

    public function primaryKeys(string|array $columns): static
    {
        $this->primaryKeys = (array) $columns;

        return $this;
    }
}
