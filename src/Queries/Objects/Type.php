<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Enums\TypeEnum;

class Type
{
    public function __construct(
        public TypeEnum $type,
        public ?int $size = null
    ) {
    }
}
