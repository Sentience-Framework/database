<?php

namespace Sentience\Database\Dialects;

use Sentience\Database\Driver;
use Sentience\Database\Queries\Objects\Alias;
use Sentience\Database\Queries\Objects\AlterColumn;
use Sentience\Database\Queries\Objects\Column;
use Sentience\Database\Queries\Objects\Condition;
use Sentience\Database\Queries\Objects\DropConstraint;
use Sentience\Database\Queries\Objects\OnConflict;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Queries\Query;

class MySQLDialect extends SQLDialect
{
    protected const string DATETIME_FORMAT = 'Y-m-d H:i:s.u';
    protected const bool ESCAPE_ANSI = false;
    protected const string ESCAPE_IDENTIFIER = '`';
    protected const string ESCAPE_STRING = '"';
    protected const bool GENERATED_BY_DEFAULT_AS_IDENTITY = false;

    public function createTable(
        bool $ifNotExists,
        string|array|Alias|Raw $table,
        array $columns,
        array $primaryKeys,
        array $constraints
    ): QueryWithParams {
        foreach ($columns as $column) {
            if ($column->generatedByDefaultAsIdentity && !in_array($column->name, $primaryKeys)) {
                $primaryKeys[] = $column->name;
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

    protected function buildConditionRegex(string &$query, array &$params, Condition $condition): void
    {
        if ($this->driver == Driver::MYSQL && $this->version >= 80000) {
            parent::buildConditionRegex($query, $params, $condition);

            return;
        }

        parent::buildConditionRegexOperator(
            $query,
            $params,
            $condition,
            'REGEXP',
            'NOT REGEXP'
        );
    }

    protected function buildOnConflict(string &$query, array &$params, ?OnConflict $onConflict, array $values, ?string $lastInsertId): void
    {
        if (is_null($onConflict)) {
            return;
        }

        $insertIgnore = is_null($onConflict->updates);

        if ($insertIgnore && !$lastInsertId) {
            $query = substr_replace($query, 'INSERT IGNORE', 0, 6);

            return;
        }

        $updates = !$insertIgnore
            ? count($onConflict->updates) > 0 ? $onConflict->updates : $values
            : [];

        if ($lastInsertId) {
            $updates[$lastInsertId] = Query::raw(
                sprintf(
                    'LAST_INSERT_ID(%s)',
                    $this->escapeIdentifier($lastInsertId)
                )
            );
        }

        $query .= sprintf(
            ' ON DUPLICATE KEY UPDATE %s',
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

    protected function buildReturning(string &$query, ?array $returning): void
    {
        if (str_starts_with($query, 'UPDATE')) {
            return;
        }

        parent::buildReturning($query, $returning);
    }

    protected function buildColumn(Column $column): string
    {
        $sql = parent::buildColumn($column);

        if ($column->generatedByDefaultAsIdentity) {
            $sql .= ' AUTO_INCREMENT';
        }

        return $sql;
    }

    protected function buildAlterTableAlterColumn(AlterColumn $alterColumn): string
    {
        return substr_replace(
            parent::buildAlterTableAlterColumn($alterColumn),
            'MODIFY',
            0,
            5
        );
    }

    protected function buildAlterTableDropConstraint(DropConstraint $dropConstraint): string
    {
        return substr_replace(
            parent::buildAlterTableDropConstraint($dropConstraint),
            'INDEX',
            5,
            10
        );
    }

    public function onConflict(): bool
    {
        if ($this->driver == Driver::MARIADB) {
            return true;
        }

        return $this->version >= 401;
    }

    public function returning(): bool
    {
        if ($this->driver != Driver::MARIADB) {
            return false;
        }

        return $this->version >= 100500;
    }
}
