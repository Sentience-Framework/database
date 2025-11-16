<?php

namespace Sentience\Database\Dialects;

use Sentience\Database\Exceptions\QueryException;
use Sentience\Database\Queries\Objects\AddForeignKeyConstraint;
use Sentience\Database\Queries\Objects\AddPrimaryKeys;
use Sentience\Database\Queries\Objects\AddUniqueConstraint;
use Sentience\Database\Queries\Objects\Alias;
use Sentience\Database\Queries\Objects\AlterColumn;
use Sentience\Database\Queries\Objects\Column;
use Sentience\Database\Queries\Objects\DropConstraint;
use Sentience\Database\Queries\Objects\OnConflict;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Queries\Objects\RenameColumn;

class SQLiteDialect extends SQLDialect
{
    protected const string DATETIME_FORMAT = 'Y-m-d H:i:s.u';
    protected const bool GENERATED_BY_DEFAULT_AS_IDENTITY = false;
    protected const bool ON_CONFLICT = true;
    protected const bool RETURNING = true;
    protected const bool SAVEPOINTS = true;

    public function createTable(
        bool $ifNotExists,
        string|array|Alias|Raw $table,
        array $columns,
        array $primaryKeys,
        array $constraints
    ): QueryWithParams {
        foreach ($columns as $column) {
            if ($column->generatedByDefaultAsIdentity) {
                $primaryKeys = array_filter(
                    $primaryKeys,
                    fn (string $primaryKey): bool => $primaryKey != $column->name
                );
            }
        }

        return parent::createTable(
            $ifNotExists,
            $table,
            $columns,
            $primaryKeys,
            $constraints
        );
    }

    protected function buildOnConflict(string &$query, array &$params, ?OnConflict $onConflict, array $values, ?string $lastInsertId): void
    {
        if (is_null($onConflict)) {
            return;
        }

        if (is_string($onConflict->conflict)) {
            throw new QueryException('SQLite does not support named constraints');
        }

        $conflict = sprintf(
            '(%s)',
            implode(
                ', ',
                array_map(
                    fn (string|Raw $column): string => $this->escapeIdentifier($column),
                    $onConflict->conflict
                )
            )
        );

        if (is_null($onConflict->updates)) {
            $query .= sprintf(' ON CONFLICT %s DO NOTHING', $conflict);

            return;
        }

        $updates = count($onConflict->updates) > 0 ? $onConflict->updates : $values;

        $query .= sprintf(
            ' ON CONFLICT %s DO UPDATE SET %s',
            $conflict,
            implode(
                ', ',
                array_map(
                    function (mixed $value, string $key) use (&$params): string {
                        if ($value instanceof Raw) {
                            return sprintf(
                                '%s = %s',
                                $this->escapeIdentifier($key),
                                (string) $value
                            );
                        }

                        $params[] = $value;

                        return sprintf('%s = ?', $this->escapeIdentifier($key));
                    },
                    $updates,
                    array_keys($updates)
                )
            )
        );
    }

    protected function buildColumn(Column $column): string
    {
        if ($column->generatedByDefaultAsIdentity) {
            return sprintf(
                '%s INTEGER PRIMARY KEY AUTOINCREMENT',
                $this->escapeIdentifier($column->name)
            );
        }

        return parent::buildColumn($column);
    }

    protected function buildAlterTableAlterColumn(AlterColumn $alterColumn): string
    {
        throw new QueryException('SQLite does not support altering columns');
    }

    protected function buildAlterTableRenameColumn(RenameColumn $renameColumn): string
    {
        if ($this->version < 32500) {
            throw new QueryException('SQLite does not support renaming columns');
        }

        return parent::buildAlterTableRenameColumn($renameColumn);
    }

    protected function buildAlterTableAddPrimaryKeys(AddPrimaryKeys $addPrimaryKeys): string
    {
        throw new QueryException('SQLite does not support adding primary keys by altering the table');
    }

    protected function buildAlterTableAddUniqueConstraint(AddUniqueConstraint $addUniqueConstraint): string
    {
        throw new QueryException('SQLite does not support adding constraints by altering the table');
    }

    protected function buildAlterTableAddForeignKeyConstraint(AddForeignKeyConstraint $addForeignKeyConstraint): string
    {
        throw new QueryException('SQLite does not support adding constraints by altering the table');
    }

    protected function buildAlterTableDropConstraint(DropConstraint $dropConstraint): string
    {
        throw new QueryException('SQLite does not support dropping constraints by altering the table');
    }

    public function onConflict(): bool
    {
        return $this->version >= 32400;
    }

    public function returning(): bool
    {
        return $this->version >= 33500;
    }
}
