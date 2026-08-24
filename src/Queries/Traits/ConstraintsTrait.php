<?php

namespace Sentience\Database\Queries\Traits;

use Sentience\Database\Queries\Enums\ReferentialActionEnum;
use Sentience\Database\Queries\Objects\CheckConstraint;
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

    public function foreignKeyConstraint(string|array $columns, string $referenceTable, string|array $referenceColumns, ?string $name = null, null|string|ReferentialActionEnum $onUpdate = null, null|string|ReferentialActionEnum $onDelete = null): static
    {
        $this->constraints[] = new ForeignKeyConstraint((array) $columns, $referenceTable, (array) $referenceColumns, $name, $onUpdate, $onDelete);

        return $this;
    }

    public function checkConstraint(callable $check, ?string $name = null): static
    {
        $checkConstraint = new CheckConstraint($name);

        $checkConstraint = $check($checkConstraint) ?? $checkConstraint;

        if (!($checkConstraint instanceof CheckConstraint)) {
            return $this;
        }

        if (count($checkConstraint->conditions()) == 0) {
            return $this;
        }

        $this->constraints[] = $checkConstraint;

        return $this;
    }

    public function constraint(string $sql): static
    {
        $this->constraints[] = Query::raw($sql);

        return $this;
    }
}
