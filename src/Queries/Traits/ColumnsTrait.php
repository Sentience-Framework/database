<?php

namespace Sentience\Database\Queries\Traits;

trait ColumnsTrait
{
    protected array $columns = [];

    public function columns(array $columns): static
    {
        $this->columns = $columns;

        return $this;
    }
}
