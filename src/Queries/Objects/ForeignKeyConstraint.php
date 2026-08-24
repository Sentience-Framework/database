<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Enums\ReferentialActionEnum;

class ForeignKeyConstraint
{
    public function __construct(
        public string $column,
        public string $referenceTable,
        public string $referenceColumn,
        public ?string $name,
        public null|string|ReferentialActionEnum $onUpdate,
        public null|string|ReferentialActionEnum $onDelete
    ) {
    }
}
