<?php

namespace Sentience\Database\Queries\Interfaces;

use Sentience\Database\Queries\Enums\ChainEnum;

interface ConditionGroup
{
    public function __construct(ChainEnum $chain, bool $not);
    public function chain(): ChainEnum;
    public function not(): bool;
    public function conditions(): array;
}
