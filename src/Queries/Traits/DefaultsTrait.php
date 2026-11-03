<?php

namespace Sentience\Database\Queries\Traits;

trait DefaultsTrait
{
    protected array $defaults = [];

    public function defaults(array $defaults): static
    {
        $this->defaults = $defaults;

        return $this;
    }
}
