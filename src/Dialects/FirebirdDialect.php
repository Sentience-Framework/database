<?php

namespace Sentience\Database\Dialects;

use Sentience\Database\Driver;
use Sentience\Database\Exceptions\QueryException;
use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Objects\Alias;
use Sentience\Database\Queries\Objects\Column;
use Sentience\Database\Queries\Objects\Condition;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Objects\Raw;

class FirebirdDialect extends SQLDialect
{
    public const string DATETIME_FORMAT = 'Y-m-d H:i:s.v';
    public const bool BOOL = true;
    public const bool RETURNING = true;

    public function __construct(Driver $driver, int|string $version)
    {
        if (is_int($version)) {
            parent::__construct($driver, $version);

            return;
        }

        preg_match('/Firebird\s(\d+\.\d+)/', $version, $match);

        parent::__construct($driver, $match[1] ?? $version);
    }

    public function createTable(
        bool $ifNotExists,
        string|array|Alias|Raw $table,
        array $columns,
        array $primaryKeys,
        array $constraints
    ): QueryWithParams {
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
            throw new QueryException('Firebird create table query requires table as string');
        }

        $query = sprintf(
            'EXECUTE BLOCK AS BEGIN IF(NOT EXISTS(SELECT 1 FROM rdb$relations WHERE rdb$relation_name = %s)) THEN BEGIN EXECUTE STATEMENT %s; END END',
            $this->escapeString($table),
            $this->escapeString($createTableQuery->toSql($this))
        );

        return new QueryWithParams($query);
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
            throw new QueryException('Firebird drop table query requires table as string');
        }

        $query = sprintf(
            'EXECUTE BLOCK AS BEGIN IF(EXISTS(SELECT 1 FROM rdb$relations WHERE rdb$relation_name = %s)) THEN BEGIN EXECUTE STATEMENT %s; END END',
            $this->escapeString($table),
            $this->escapeString($dropTableQuery->toSql($this))
        );

        return new QueryWithParams($query);
    }

    protected function buildConditionRegex(string &$query, array &$params, Condition $condition): void
    {
        parent::buildConditionRegexOperator(
            $query,
            $params,
            $condition,
            'SIMILAR TO',
            'NOT SIMILAR TO'
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

        $query .= ' ROWS ' . $limit;
    }

    protected function buildOffset(string &$query, ?int $limit, ?int $offset): void
    {
        if (in_array(null, [$limit, $offset], true)) {
            return;
        }

        $rows = $offset + 1;
        $to = $rows + $limit - 1;

        $query .= sprintf(
            ' ROWS %d TO %d',
            $rows,
            $to
        );
    }

    protected function buildColumn(Column $column): string
    {
        return parent::buildColumn($column);
    }

    public function type(TypeEnum $type, ?int $size = null): string
    {
        return match ($type) {
            TypeEnum::BOOL => 'BOOLEAN',
            TypeEnum::STRING => sprintf('VARCHAR(%d)', min((int) $size, 255)),
            TypeEnum::DATETIME => 'TIMESTAMP',
            default => parent::type($type, $size)
        };
    }
}
