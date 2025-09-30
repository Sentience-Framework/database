<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Queries\Objects\AddColumn;
use Sentience\Database\Queries\Objects\AddForeignKeyConstraint;
use Sentience\Database\Queries\Objects\AddPrimaryKeys;
use Sentience\Database\Queries\Objects\AddUniqueConstraint;
use Sentience\Database\Queries\Objects\AlterColumn;
use Sentience\Database\Queries\Objects\DropColumn;
use Sentience\Database\Queries\Objects\DropConstraint;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Objects\RenameColumn;
use Sentience\Database\Results\ResultInterface;

class AlterTableQuery extends Query
{
    protected array $alters = [];

    public function toQueryWithParams(): array
    {
        return $this->dialect->alterTable(
            $this->table,
            $this->alters
        );
    }

    public function toRawQuery(): array
    {
        $queriesWithParams = $this->toQueryWithParams();

        return array_map(
            fn (QueryWithParams $queryWithParams): string => $queryWithParams->toRawQuery($this->dialect),
            $queriesWithParams
        );
    }

    public function execute(): array
    {
        $queries = $this->toQueryWithParams();

        return array_map(
            fn (QueryWithParams $queryWithParams): ResultInterface => $this->database->queryWithParams($queryWithParams),
            $queries
        );
    }

    public function addColumn(string $name, string $type, bool $notNull = false, mixed $default = null, array $options = []): static
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

    public function addForeignKeyConstraint(string $column, string $referenceTable, string $referenceColumn, ?string $name = null): static
    {
        $this->alters[] = new AddForeignKeyConstraint($column, $referenceTable, $referenceColumn, $name);

        return $this;
    }

    public function dropConstraint(string $constraint): static
    {
        $this->alters[] = new DropConstraint($constraint);

        return $this;
    }
}
