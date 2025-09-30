<?php

namespace Sentience\Database\Queries\Objects;

class ForeignKeyConstraint
{
    public function __construct(
        public string $column,
        public string $referenceTable,
        public string $referenceColumn,
        public ?string $name,
        public array $referentialActions
    ) {
    }
}
