<?php

namespace Sentience\Database\Queries\Traits;

trait IfNotExistsTrait
{
    protected bool $ifNotExists = false;
    protected bool $emulateIfNotExists = false;

    public function ifNotExists(): static
    {
        $this->ifNotExists = true;

        return $this;
    }

    public function emulateIfNotExists(): static
    {
        $this->emulateIfNotExists = true;

        return $this;
    }
}
