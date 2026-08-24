<?php

namespace Sentience\Database\Queries\Traits;

trait SetTrait
{
    protected array $set = [];

    public function set(array $set): static
    {
        $this->set = $set;

        return $this;
    }
}
