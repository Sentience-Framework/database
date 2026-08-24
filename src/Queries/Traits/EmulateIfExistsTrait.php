<?php

namespace Sentience\Database\Queries\Traits;

trait EmulateIfExistsTrait
{
    protected bool $emulateIfExists = false;

    public function emulateIfExists(): static
    {
        $this->emulateIfExists = true;

        return $this;
    }
}
