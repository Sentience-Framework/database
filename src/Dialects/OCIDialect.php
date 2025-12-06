<?php

namespace Sentience\Database\Dialects;

use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Objects\Alias;
use Sentience\Database\Queries\Objects\OnConflict;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Objects\Raw;

class OCIDialect extends SQLDialect
{
    protected const string DATETIME_FORMAT = 'Y-m-d H:i:s.u';

    public function createTable(
        bool $ifNotExists,
        array|string|Alias|Raw $table,
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

        $query = sprintf(
            'BEGIN EXECUTE IMMEDIATE %s; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -955 THEN RAISE; END IF; END;',
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

        $query = sprintf(
            'BEGIN EXECUTE IMMEDIATE %s; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;',
            $this->escapeString($dropTableQuery->toSql($this))
        );

        return new QueryWithParams($query);
    }

    protected function buildLimit(string &$query, ?int $limit, ?int $offset): void
    {
        if (in_array(null, [$limit, $offset], true)) {
            return;
        }

        $query .= sprintf(' OFFSET %d ROWS FETCH NEXT %d ROWS ONLY', $offset ?? 0, $limit);
    }

    protected function buildOffset(string &$query, ?int $limit, ?int $offset): void
    {
        if (!is_null($limit) && is_null($offset)) {
            return;
        }

        $query .= sprintf(' OFFSET %d ROWS', $offset);
    }

    protected function buildOnConflict(string &$query, array &$params, ?OnConflict $onConflict, array $values, ?string $lastInsertId): void
    {
        /**
         * OCI relies on Sentience's returning fallback
         */

        return;
    }

    protected function buildReturning(string &$query, ?array $returning): void
    {
        /**
         * OCI relies on Sentience's returning fallback
         */

        return;
    }

    public function type(TypeEnum $type, ?int $size = null): string
    {
        return match ($type) {
            TypeEnum::BOOL => 'NUMBER(1)',
            TypeEnum::INT => $size > 32 ? 'NUMBER(19)' : 'NUMBER(10)',
            TypeEnum::FLOAT => $size > 32 ? 'NUMBER(30, 15)' : 'NUMBER(15, 7)',
            TypeEnum::STRING => $size > 4000 ? 'CLOB' : sprintf('VARCHAR2(%d)', $size ?? 255),
            TypeEnum::DATETIME => 'TIMESTAMP'
        };
    }
}
