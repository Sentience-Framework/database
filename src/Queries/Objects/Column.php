<?php

namespace Sentience\Database\Queries\Objects;

class Column
{
    public function __construct(
        public string $name,
        public string $type,
        public bool $notNull,
        public mixed $default,
        public bool $generatedByDefaultAsIdentity
    ) {
    }
}
