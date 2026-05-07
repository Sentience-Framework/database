<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Enums\ChainEnum;
use Sentience\Database\Queries\Interfaces\ConditionGroup;
use Sentience\Database\Queries\Traits\HavingTrait;

class HavingGroup implements ConditionGroup
{
    use HavingTrait;

    public function __construct(
        protected ChainEnum $chain,
        protected bool $not
    ) {
    }

    public function chain(): ChainEnum
    {
        return $this->chain;
    }

    public function not(): bool
    {
        return $this->not;
    }

    public function conditions(): array
    {
        return $this->having;
    }
}
