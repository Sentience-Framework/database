<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Enums\ChainEnum;
use Sentience\Database\Queries\Interfaces\ConditionGroup;
use Sentience\Database\Queries\Traits\WhereTrait;

class WhereGroup implements ConditionGroup
{
    use WhereTrait;

    public function __construct(protected ChainEnum $chain)
    {
    }

    public function getChain(): ChainEnum
    {
        return $this->chain;
    }

    public function getConditions(): array
    {
        return $this->where;
    }
}
