<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Traits\WhereTrait;

class CheckConstraint
{
    use WhereTrait;

    public function __construct(public ?string $name)
    {
    }

    public function conditions(): array
    {
        return $this->where;
    }
}
