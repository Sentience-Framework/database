<?php

namespace Sentience\Database\Queries\Traits;

trait EmulateIfNotExistsTrait
{
    protected bool $emulateIfNotExists = false;

    public function emulateIfNotExists(): static
    {
        $this->emulateIfNotExists = true;

        return $this;
    }
}
