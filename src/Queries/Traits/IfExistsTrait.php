<?php

namespace Sentience\Database\Queries\Traits;

trait IfExistsTrait
{
    protected bool $ifExists = false;
    protected bool $emulateIfExists = false;

    public function ifExists(): static
    {
        $this->ifExists = true;

        return $this;
    }

    public function emulateIfExists(): static
    {
        $this->emulateIfExists = true;

        return $this;
    }
}
