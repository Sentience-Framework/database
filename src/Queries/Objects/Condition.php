<?php

namespace Sentience\Database\Queries\Objects;

use BackedEnum;
use DateTimeInterface;
use Sentience\Database\Queries\Enums\ChainEnum;
use Sentience\Database\Queries\SelectQuery;

class Condition
{
    public function __construct(
        public string|BackedEnum $condition,
        public null|string|array $identifier,
        public null|bool|int|float|string|array|DateTimeInterface|Identifier|Raw|SelectQuery $value,
        public ChainEnum $chain
    ) {
    }
}
