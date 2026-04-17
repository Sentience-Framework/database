<?php

namespace Sentience\Database\Queries\Objects;

use DateTime;
use DateTimeInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Interfaces\Sql;
use Sentience\Database\Queries\SelectQuery;

class Expression implements Sql
{
    public function __construct(
        protected string $sql,
        protected array $params
    ) {
    }

    public function sql(DialectInterface $dialect): string
    {
        return $this->sql;
    }

    public function params(DialectInterface $dialect): array
    {
        return $this->params;
    }

    public function rawSql(DialectInterface $dialect): string
    {
        $queryWithParams = new QueryWithParams(
            $this->sql($dialect),
            $this->params($dialect)
        );

        return $queryWithParams->toSql($this->extendDialect($dialect));
    }

    protected function extendDialect(DialectInterface $dialect): DialectInterface
    {
        return new class ($dialect) implements DialectInterface {
            public function __construct(protected DialectInterface $dialect)
            {
            }

            public function select(
                ?array $distinct,
                array $columns,
                string|array|Alias|Sql|SubQuery $table,
                array $joins,
                array $where,
                array $groupBy,
                array $having,
                array $orderBy,
                ?int $limit,
                ?int $offset,
                array $unions
            ): QueryWithParams {
                return $this->dialect->select(
                    $distinct,
                    $columns,
                    $table,
                    $joins,
                    $where,
                    $groupBy,
                    $having,
                    $orderBy,
                    $limit,
                    $offset,
                    $unions
                );
            }

            public function insert(
                string|array|Sql $table,
                array $values,
                ?OnConflict $onConflict,
                ?array $returning,
                ?string $lastInsertId
            ): QueryWithParams {
                return $this->dialect->insert(
                    $table,
                    $values,
                    $onConflict,
                    $returning,
                    $lastInsertId
                );
            }

            public function update(
                string|array|Sql $table,
                array $updates,
                array $where,
                ?array $returning
            ): QueryWithParams {
                return $this->dialect->update(
                    $table,
                    $updates,
                    $where,
                    $returning
                );
            }

            public function delete(
                string|array|Sql $table,
                array $where,
                ?array $returning
            ): QueryWithParams {
                return $this->dialect->delete(
                    $table,
                    $where,
                    $returning
                );
            }

            public function createTable(
                bool $ifNotExists,
                string|array|Sql $table,
                array $columns,
                array $primaryKeys,
                array $constraints
            ): QueryWithParams {
                return $this->dialect->createTable(
                    $ifNotExists,
                    $table,
                    $columns,
                    $primaryKeys,
                    $constraints
                );
            }

            public function alterTable(string|array|Sql $table, array $alters): array
            {
                return $this->dialect->alterTable(
                    $table,
                    $alters
                );
            }

            public function dropTable(bool $ifExists, string|array|Sql $table): QueryWithParams
            {
                return $this->dialect->dropTable(
                    $ifExists,
                    $table
                );
            }

            public function beginTransaction(?string $name): QueryWithParams
            {
                return $this->dialect->beginTransaction($name);
            }

            public function commitTransaction(?string $name): QueryWithParams
            {
                return $this->dialect->commitTransaction($name);
            }

            public function rollbackTransaction(?string $name): QueryWithParams
            {
                return $this->dialect->rollbackTransaction($name);
            }

            public function beginSavepoint(string $name): QueryWithParams
            {
                return $this->dialect->beginSavepoint($name);
            }

            public function commitSavepoint(string $name): QueryWithParams
            {
                return $this->dialect->commitSavepoint($name);
            }

            public function rollbackSavepoint(string $name): QueryWithParams
            {
                return $this->dialect->rollbackSavepoint($name);
            }

            public function escapeIdentifier(string|array|Alias|Sql $identifier): string
            {
                return $this->dialect->escapeIdentifier($identifier);
            }

            public function escapeString(string $string): string
            {
                return $this->dialect->escapeString($string);
            }

            public function castToDriver(null|bool|int|float|string|DateTimeInterface $value): null|bool|int|float|string
            {
                return $this->dialect->castToDriver($value);
            }

            public function castToQuery(null|bool|int|float|string|DateTimeInterface|SelectQuery|Sql $value): string
            {
                if ($value instanceof SelectQuery) {
                    return sprintf('(%s)', $value->toSql());
                }

                if ($value instanceof Sql) {
                    return $value->rawSql($this->dialect);
                }

                return $this->dialect->castToQuery($value);
            }

            public function castBool(bool $bool): null|bool|int|float|string
            {
                return $this->dialect->castBool($bool);
            }

            public function castDateTime(DateTimeInterface $dateTime): null|bool|int|float|string
            {
                return $this->dialect->castDateTime($dateTime);
            }

            public function parseBool(null|bool|int|float|string $bool): bool
            {
                return $this->dialect->parseBool($bool);
            }

            public function parseDateTime(string $string): ?DateTime
            {
                return $this->dialect->parseDateTime($string);
            }

            public function type(TypeEnum $type, ?int $size = null): string
            {
                return $this->dialect->type($type, $size);
            }

            public function bool(): bool
            {
                return $this->dialect->bool();
            }

            public function generatedByDefaultAsIdentity(): bool
            {
                return $this->dialect->generatedByDefaultAsIdentity();
            }

            public function onConflict(): bool
            {
                return $this->dialect->onConflict();
            }

            public function returning(): bool
            {
                return $this->dialect->returning();
            }

            public function savepoints(): bool
            {
                return $this->dialect->savepoints();
            }
        };
    }
}
