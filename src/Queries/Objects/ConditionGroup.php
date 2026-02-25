<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Enums\ChainEnum;
use Sentience\Database\Queries\Traits\WhereTrait;

class ConditionGroup
{
    use WhereTrait;

    public function __construct(public ChainEnum $chain, array $whereMacros)
    {
        $this->whereMacros = $whereMacros;
    }

    public function getConditions(): array
    {
        return $this->where;
    }
}
