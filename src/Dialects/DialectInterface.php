<?php

namespace Sentience\Database\Dialects;

use DateTime;
use DateTimeInterface;
use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Interfaces\Sql;
use Sentience\Database\Queries\Objects\Alias;
use Sentience\Database\Queries\Objects\Having;
use Sentience\Database\Queries\Objects\OnConflict;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Objects\SubQuery;

interface DialectInterface
{
    public function select(
        bool $distinct,
        array $columns,
        string|array|Alias|Sql|SubQuery $table,
        array $joins,
        array $where,
        array $groupBy,
        ?Having $having,
        array $orderBy,
        ?int $limit,
        ?int $offset
    ): QueryWithParams;

    public function insert(
        string|array|Sql $table,
        array $values,
        ?OnConflict $onConflict,
        ?array $returning,
        ?string $lastInsertId
    ): QueryWithParams;

    public function update(
        string|array|Sql $table,
        array $values,
        array $where,
        ?array $returning
    ): QueryWithParams;

    public function delete(
        string|array|Sql $table,
        array $where,
        ?array $returning
    ): QueryWithParams;

    public function createTable(
        bool $ifNotExists,
        string|array|Sql $table,
        array $columns,
        array $primaryKeys,
        array $constraints
    ): QueryWithParams;

    public function alterTable(
        string|array|Sql $table,
        array $alters
    ): array;

    public function dropTable(
        bool $ifExists,
        string|array|Sql $table
    ): QueryWithParams;

    public function beginTransaction(
        ?string $name
    ): QueryWithParams;

    public function commitTransaction(
        ?string $name
    ): QueryWithParams;

    public function rollbackTransaction(
        ?string $name
    ): QueryWithParams;

    public function beginSavepoint(
        string $name
    ): QueryWithParams;

    public function commitSavepoint(
        string $name
    ): QueryWithParams;

    public function rollbackSavepoint(
        string $name
    ): QueryWithParams;

    public function escapeIdentifier(string|array|Alias|Sql $identifier): string;
    public function escapeString(string $string): string;
    public function castToDriver(null|bool|int|float|string|DateTimeInterface $value): null|bool|int|float|string;
    public function castToQuery(null|bool|int|float|string|DateTimeInterface $value): string;
    public function castBool(bool $bool): null|bool|int|float|string;
    public function castDateTime(DateTimeInterface $dateTime): null|bool|int|float|string;
    public function parseBool(null|bool|int|float|string $bool): bool;
    public function parseDateTime(string $string): ?DateTime;
    public function type(TypeEnum $type, ?int $size = null): string;
    public function bool(): bool;
    public function generatedByDefaultAsIdentity(): bool;
    public function onConflict(): bool;
    public function returning(): bool;
    public function savepoints(): bool;
}
