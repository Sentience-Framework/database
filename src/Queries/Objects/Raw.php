<?php

namespace Sentience\Database\Queries\Objects;

class Raw
{
    public function __construct(public string $sql)
    {
    }

    public function __tostring(): string
    {
        return $this->sql;
    }
}
