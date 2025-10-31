<?php

namespace Sentience\Database\Queries\Traits;

use Sentience\Database\Queries\Objects\Having;

trait HavingTrait
{
    protected ?Having $having = null;

    public function having(string $conditions, array $values = []): static
    {
        $this->having = new Having($conditions, $values);

        return $this;
    }
}
