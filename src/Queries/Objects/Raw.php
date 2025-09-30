<?php

namespace Sentience\Database\Queries\Objects;

use Stringable;

class Raw implements Stringable
{
    public function __construct(public string $sql)
    {
    }

    public function __toString(): string
    {
        return $this->sql;
    }
}
