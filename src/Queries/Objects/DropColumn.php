<?php

namespace Sentience\Database\Queries\Objects;

class DropColumn
{
    public function __construct(public string $column)
    {
    }
}
