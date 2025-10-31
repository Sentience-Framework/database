<?php

namespace Sentience\Database\Queries\Objects;

class Raw
{
    public function __construct(public string $sql)
    {
    }
}
