<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Enums\ChainEnum;
use Sentience\Database\Queries\Enums\ConditionEnum;

class Condition
{
    public function __construct(
        public ConditionEnum $condition,
        public null|string|array $identifier,
        public mixed $value,
        public ChainEnum $chain
    ) {
    }
}
