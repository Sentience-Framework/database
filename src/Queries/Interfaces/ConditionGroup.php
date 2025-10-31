<?php

namespace Sentience\Database\Queries\Interfaces;

use Sentience\Database\Queries\Enums\ChainEnum;

interface ConditionGroup
{
    public function __construct(ChainEnum $chain);
    public function getChain(): ChainEnum;
    public function getConditions(): array;
}
