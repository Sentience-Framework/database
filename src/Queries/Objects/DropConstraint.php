<?php

namespace Sentience\Database\Queries\Objects;

class DropConstraint
{
    public function __construct(public string $constraint)
    {
    }
}
