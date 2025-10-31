<?php

namespace Sentience\Database\Queries\Traits;

trait LastInsertIdTrait
{
    protected ?string $lastInsertId = null;

    public function lastInsertId(string $column): static
    {
        $this->lastInsertId = $column;

        return $this;
    }
}
