<?php

namespace Sentience\Database\Dialects;

use Sentience\Database\Exceptions\QueryException;
use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Objects\Alias;
use Sentience\Database\Queries\Objects\Column;
use Sentience\Database\Queries\Objects\Condition;
use Sentience\Database\Queries\Objects\OnConflict;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Queries\Query;

class SQLServerDialect extends SQLDialect
{
    public const string DATETIME_FORMAT = 'Y-m-d H:i:s.v';
    public const bool GENERATED_BY_DEFAULT_AS_IDENTITY = false;

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

        $createTableQuery = parent::createTable(
            false,
            $table,
            $columns,
            $primaryKeys,
            $constraints
        );

        if (!$ifNotExists) {
            return $createTableQuery;
        }

        if (!is_string($table)) {
            throw new QueryException('SQL Server create table query requires table as string');
        }

        $query = sprintf(
            "IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = %s AND schema_id = SCHEMA_ID('dbo')) BEGIN %s END",
            $this->escapeString($table),
            $createTableQuery->toSql($this)
        );

        return new QueryWithParams($query, $createTableQuery->params);
    }

    public function dropTable(
        bool $ifExists,
        string|array|Alias|Raw $table
    ): QueryWithParams {
        $dropTableQuery = parent::dropTable(
            false,
            $table
        );

        if (!$ifExists) {
            return $dropTableQuery;
        }

        if (!is_string($table)) {
            throw new QueryException('SQL Server drop table query requires table as string');
        }

        $query = sprintf(
            "IF EXISTS (SELECT 1 FROM sys.tables WHERE name = %s AND schema_id = SCHEMA_ID('dbo')) BEGIN %s END",
            $this->escapeString($table),
            $dropTableQuery->toSql($this)
        );

        return new QueryWithParams($query, $dropTableQuery->params);
    }

    protected function buildConditionRegex(string &$query, array &$params, Condition $condition): void
    {
        parent::buildConditionRegexOperator(
            $query,
            $params,
            $condition,
            'LIKE',
            'NOT LIKE'
        );
    }

    protected function buildLimit(string &$query, ?int $limit, ?int $offset): void
    {
        if (is_null($limit)) {
            return;
        }

        if (!is_null($offset)) {
            return;
        }

        $query = substr($query, 7, 8) == 'DISTINCT'
            ? substr_replace(
                $query,
                sprintf(
                    'SELECT DISTINCT TOP(%d)',
                    $limit
                ),
                0,
                15
            )
            : substr_replace(
                $query,
                sprintf(
                    'SELECT TOP(%d)',
                    $limit
                ),
                0,
                6
            );
    }

    protected function buildOffset(string &$query, ?int $limit, ?int $offset): void
    {
        if (in_array(null, [$limit, $offset], true)) {
            return;
        }

        $query .= sprintf(
            'OFFSET %d ROWS FETCH NEXT %d ROWS ONLY',
            $offset,
            $limit
        );
    }

    protected function buildOnConflict(string &$query, array &$params, ?OnConflict $onConflict, array $values, ?string $lastInsertId): void
    {
        /**
         * SQL Server relies on Sentience's returning fallback
         */

        return;
    }

    protected function buildReturning(string &$query, ?array $returning): void
    {
        /**
         * SQL Server relies on Sentience's returning fallback
         */

        return;
    }

    protected function buildColumn(Column $column): string
    {
        if ($column->generatedByDefaultAsIdentity) {
            $column->type .= ' identity(1, 1)';
        }

        return parent::buildColumn($column);
    }

    protected function escape(string $string, string $char): string
    {
        if ($char == static::ESCAPE_IDENTIFIER) {
            return '[' . Query::escapeAnsi($string, ['[', ']']) . ']';
        }

        return parent::escape($string, $char);
    }

    public function type(TypeEnum $type, ?int $size = null): string
    {
        return match ($type) {
            TypeEnum::BOOL => 'SMALLINT',
            TypeEnum::FLOAT => $size > 32 ? 'FLOAT(8)' : 'FLOAT(4)',
            TypeEnum::DATETIME => $size > 3 ? sprintf('DATETIME2', $size) : 'DATETIME',
            default => parent::type($type, $size)
        };
    }
}
