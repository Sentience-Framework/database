<?php

namespace Sentience\Database\Queries\Traits;

trait IfNotExistsTrait
{
    protected bool $ifNotExists = false;

    public function ifNotExists(): static
    {
        $this->ifNotExists = true;

        return $this;
    }
}
