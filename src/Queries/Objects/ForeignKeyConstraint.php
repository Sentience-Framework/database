<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Enums\ReferentialActionEnum;

class ForeignKeyConstraint
{
    public function __construct(
        public array $columns,
        public string $referenceTable,
        public array $referenceColumns,
        public ?string $name,
        public null|string|ReferentialActionEnum $onUpdate,
        public null|string|ReferentialActionEnum $onDelete
    ) {
    }
}
