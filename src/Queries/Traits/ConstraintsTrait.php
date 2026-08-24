<?php

namespace Sentience\Database\Queries\Traits;

use Sentience\Database\Queries\Enums\ReferentialActionEnum;
use Sentience\Database\Queries\Objects\ForeignKeyConstraint;
use Sentience\Database\Queries\Objects\UniqueConstraint;
use Sentience\Database\Queries\Query;

trait ConstraintsTrait
{
    protected array $constraints = [];

    public function uniqueConstraint(array $columns, ?string $name = null): static
    {
        $this->constraints[] = new UniqueConstraint($columns, $name);

        return $this;
    }

    public function foreignKeyConstraint(string $column, string $referenceTable, string $referenceColumn, ?string $name = null, null|string|ReferentialActionEnum $onUpdate = null, null|string|ReferentialActionEnum $onDelete = null): static
    {
        $this->constraints[] = new ForeignKeyConstraint($column, $referenceTable, $referenceColumn, $name, $onUpdate, $onDelete);

        return $this;
    }

    public function constraint(string $sql): static
    {
        $this->constraints[] = Query::raw($sql);

        return $this;
    }
}
