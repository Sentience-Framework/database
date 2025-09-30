<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Enums\ChainEnum;
use Sentience\Database\Queries\Enums\ConditionEnum;

class Condition
{
    public function __construct(
        public ConditionEnum $condition,
        public string|array $expression,
        public mixed $value,
        public ChainEnum $chain
    ) {
    }
}
