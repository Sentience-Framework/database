<?php

namespace Sentience\Database\Queries\Objects;

class Index
{
    public function __construct(
        public string $name,
        public array $columns,
        public bool $unique
    ) {
    }
}
