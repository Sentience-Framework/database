<?php

namespace Sentience\Database\Queries\Objects;

class Alias
{
    public function __construct(
        public string|array|Raw $identifier,
        public string $alias
    ) {
    }
}
