<?php

namespace Sentience\Database\Queries\Traits;

use DateTimeInterface;
use Sentience\Database\Queries\Objects\AddColumn;
use Sentience\Database\Queries\Objects\AddForeignKeyConstraint;
use Sentience\Database\Queries\Objects\AddPrimaryKeys;
use Sentience\Database\Queries\Objects\AddUniqueConstraint;
use Sentience\Database\Queries\Objects\AlterColumn;
use Sentience\Database\Queries\Objects\DropColumn;
use Sentience\Database\Queries\Objects\DropConstraint;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Queries\Objects\RenameColumn;
use Sentience\Database\Queries\Query;

trait AltersTrait
{
    protected array $alters = [];

    public function addColumn(string $name, string $type, bool $notNull = false, null|bool|int|float|string|DateTimeInterface|Raw $default = null, array $options = []): static
    {
        $this->alters[] = new AddColumn($name, $type, $notNull, $default, $options);

        return $this;
    }

    public function alterColumn(string $column, array $options): static
    {
        $this->alters[] = new AlterColumn($column, $options);

        return $this;
    }

    public function renameColumn(string $old, string $new): static
    {
        $this->alters[] = new RenameColumn($old, $new);

        return $this;
    }

    public function dropColumn(string $column): static
    {
        $this->alters[] = new DropColumn($column);

        return $this;
    }

    public function addPrimaryKeys(string|array $columns): static
    {
        $this->alters[] = new AddPrimaryKeys((array) $columns);

        return $this;
    }

    public function addUniqueConstraint(array $columns, ?string $name): static
    {
        $this->alters[] = new AddUniqueConstraint($columns, $name);

        return $this;
    }

    public function addForeignKeyConstraint(string $column, string $referenceTable, string $referenceColumn, ?string $name = null, array $referentialActions = []): static
    {
        $this->alters[] = new AddForeignKeyConstraint($column, $referenceTable, $referenceColumn, $name, $referentialActions);

        return $this;
    }

    public function dropConstraint(string $constraint): static
    {
        $this->alters[] = new DropConstraint($constraint);

        return $this;
    }

    public function alter(string $sql): static
    {
        $this->alters[] = Query::raw($sql);

        return $this;
    }
}
