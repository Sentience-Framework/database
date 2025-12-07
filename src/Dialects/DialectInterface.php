<?php

namespace Sentience\Database\Dialects;

use DateTime;
use DateTimeInterface;
use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Objects\Alias;
use Sentience\Database\Queries\Objects\Having;
use Sentience\Database\Queries\Objects\OnConflict;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Objects\Raw;

interface DialectInterface
{
    public function select(
        bool $distinct,
        array $columns,
        string|array|Alias|Raw $table,
        array $joins,
        array $where,
        array $groupBy,
        ?Having $having,
        array $orderBy,
        ?int $limit,
        ?int $offset
    ): QueryWithParams;

    public function insert(
        string|array|Alias|Raw $table,
        array $values,
        ?OnConflict $onConflict,
        ?array $returning,
        ?string $lastInsertId
    ): QueryWithParams;

    public function update(
        string|array|Alias|Raw $table,
        array $values,
        array $where,
        ?array $returning
    ): QueryWithParams;

    public function delete(
        string|array|Alias|Raw $table,
        array $where,
        ?array $returning
    ): QueryWithParams;

    public function createTable(
        bool $ifNotExists,
        string|array|Alias|Raw $table,
        array $columns,
        array $primaryKeys,
        array $constraints
    ): QueryWithParams;

    public function alterTable(
        string|array|Alias|Raw $table,
        array $alters
    ): array;

    public function dropTable(
        bool $ifExists,
        string|array|Alias|Raw $table
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

    public function escapeIdentifier(string|array|Alias|Raw $identifier): string;
    public function escapeString(string $string): string;
    public function castToDriver(mixed $value): mixed;
    public function castToQuery(mixed $value): mixed;
    public function castBool(bool $bool): mixed;
    public function castDateTime(DateTimeInterface $dateTime): mixed;
    public function parseBool(mixed $bool): bool;
    public function parseDateTime(string $string): ?DateTime;
    public function type(TypeEnum $type, ?int $size = null): string;
    public function bool(): bool;
    public function generatedByDefaultAsIdentity(): bool;
    public function onConflict(): bool;
    public function returning(): bool;
    public function savepoints(): bool;
}
