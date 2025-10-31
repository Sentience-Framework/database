<?php

namespace Sentience\Database\Queries\Objects;

class Identifier
{
    public function __construct(public string|array|Raw $identifier)
    {
    }
}
