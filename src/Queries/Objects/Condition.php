<?php

namespace Sentience\Database\Queries\Objects;

use BackedEnum;
use Sentience\Database\Queries\Enums\ChainEnum;

class Condition
{
    public function __construct(
        public string|BackedEnum $condition,
        public null|string|array $identifier,
        public mixed $value,
        public ChainEnum $chain
    ) {
    }
}
