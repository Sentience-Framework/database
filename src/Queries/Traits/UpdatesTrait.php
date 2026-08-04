<?php

namespace Sentience\Database\Queries\Traits;

trait UpdatesTrait
{
    protected array $updates = [];

    public function updates(array $updates): static
    {
        $this->updates = $updates;

        return $this;
    }
}
