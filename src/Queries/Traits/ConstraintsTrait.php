<?php

namespace Sentience\Database\Queries\Traits;

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

    public function foreignKeyConstraint(string $column, string $referenceTable, string $referenceColumn, ?string $name = null, array $referentialActions = []): static
    {
        $this->constraints[] = new ForeignKeyConstraint($column, $referenceTable, $referenceColumn, $name, $referentialActions);

        return $this;
    }

    public function constraint(string $sql): static
    {
        $this->constraints[] = Query::raw($sql);

        return $this;
    }
}
