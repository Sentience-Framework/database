<?php

namespace Sentience\Database\Queries\Traits;

use BackedEnum;
use DateTimeInterface;
use Sentience\Database\Queries\Enums\ChainEnum;
use Sentience\Database\Queries\Interfaces\Sql;
use Sentience\Database\Queries\Objects\WhereGroup;
use Sentience\Database\Queries\SelectQuery;

trait WhereTrait
{
    use ConditionsTrait;

    protected array $where = [];

    public function whereEquals(string|array $column, null|bool|int|float|string|DateTimeInterface|SelectQuery|Sql $value, bool $cast = false): static
    {
        return $this->equals($this->where, $column, $value, $cast, ChainEnum::And);
    }

    public function whereNotEquals(string|array $column, null|bool|int|float|string|DateTimeInterface|SelectQuery|Sql $value, bool $cast = false): static
    {
        return $this->notEquals($this->where, $column, $value, $cast, ChainEnum::And);
    }

    public function whereIsNull(string|array $column): static
    {
        return $this->isNull($this->where, $column, ChainEnum::And);
    }

    public function whereIsNotNull(string|array $column): static
    {
        return $this->isNotNull($this->where, $column, ChainEnum::And);
    }

    public function whereLike(string|array $column, string $value, bool $caseInsensitive = false): static
    {
        return $this->like($this->where, $column, $value, $caseInsensitive, ChainEnum::And);
    }

    public function whereNotLike(string|array $column, string $value, bool $caseInsensitive = false): static
    {
        return $this->notLike($this->where, $column, $value, $caseInsensitive, ChainEnum::And);
    }

    public function whereStartsWith(string|array $column, string $value, bool $caseInsensitive = false, bool $escapeBackslash = false): static
    {
        return $this->startsWith($this->where, $column, $value, $caseInsensitive, $escapeBackslash, ChainEnum::And);
    }

    public function whereEndsWith(string|array $column, string $value, bool $caseInsensitive = false, bool $escapeBackslash = false): static
    {
        return $this->endsWith($this->where, $column, $value, $caseInsensitive, $escapeBackslash, ChainEnum::And);
    }

    public function whereContains(string|array $column, string $value, bool $caseInsensitive = false, bool $escapeBackslash = false): static
    {
        return $this->contains($this->where, $column, $value, $caseInsensitive, $escapeBackslash, ChainEnum::And);
    }

    public function whereNotContains(string|array $column, string $value, bool $caseInsensitive = false, bool $escapeBackslash = false): static
    {
        return $this->notContains($this->where, $column, $value, $caseInsensitive, $escapeBackslash, ChainEnum::And);
    }

    public function whereGlob(string|array $column, string $value, bool $caseInsensitive = false): static
    {
        return $this->glob($this->where, $column, $value, $caseInsensitive, ChainEnum::And);
    }

    public function whereNotGlob(string|array $column, string $value, bool $caseInsensitive = false): static
    {
        return $this->notGlob($this->where, $column, $value, $caseInsensitive, ChainEnum::And);
    }

    public function whereIn(string|array $column, array|SelectQuery $values): static
    {
        return $this->in($this->where, $column, $values, ChainEnum::And);
    }

    public function whereNotIn(string|array $column, array|SelectQuery $values): static
    {
        return $this->notIn($this->where, $column, $values, ChainEnum::And);
    }

    public function whereLessThan(string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $value): static
    {
        return $this->lessThan($this->where, $column, $value, ChainEnum::And);
    }

    public function whereLessThanOrEquals(string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $value): static
    {
        return $this->lessThanOrEquals($this->where, $column, $value, ChainEnum::And);
    }

    public function whereGreaterThan(string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $value): static
    {
        return $this->greaterThan($this->where, $column, $value, ChainEnum::And);
    }

    public function whereGreaterThanOrEquals(string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $value): static
    {
        return $this->greaterThanOrEquals($this->where, $column, $value, ChainEnum::And);
    }

    public function whereBetween(string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $min, int|float|string|DateTimeInterface|SelectQuery|Sql $max): static
    {
        return $this->between($this->where, $column, $min, $max, ChainEnum::And);
    }

    public function whereNotBetween(string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $min, int|float|string|DateTimeInterface|SelectQuery|Sql $max): static
    {
        return $this->notBetween($this->where, $column, $min, $max, ChainEnum::And);
    }

    public function whereEmpty(string|array $column): static
    {
        return $this->empty($this->where, $column, ChainEnum::And);
    }

    public function whereNotEmpty(string|array $column): static
    {
        return $this->notEmpty($this->where, $column, ChainEnum::And);
    }

    public function whereRegex(string|array $column, string $pattern, string $flags = ''): static
    {
        return $this->regex($this->where, $column, $pattern, $flags, ChainEnum::And);
    }

    public function whereNotRegex(string|array $column, string $pattern, string $flags = ''): static
    {
        return $this->notRegex($this->where, $column, $pattern, $flags, ChainEnum::And);
    }

    public function whereExists(SelectQuery $selectQuery): static
    {
        return $this->exists($this->where, $selectQuery, ChainEnum::And);
    }

    public function whereNotExists(SelectQuery $selectQuery): static
    {
        return $this->notExists($this->where, $selectQuery, ChainEnum::And);
    }

    public function whereGroup(callable $callback): static
    {
        return $this->group($this->where, $callback, false, WhereGroup::class, ChainEnum::And);
    }

    public function whereNotGroup(callable $callback): static
    {
        return $this->group($this->where, $callback, true, WhereGroup::class, ChainEnum::And);
    }

    public function whereOperator(string|array $column, string|BackedEnum $operator, null|bool|int|float|string|array|DateTimeInterface|SelectQuery|Sql $value): static
    {
        return $this->operator($this->where, $column, $operator, $value, ChainEnum::And);
    }

    public function wheref(string $format, null|bool|int|float|string|DateTimeInterface|SelectQuery|Sql ...$values): static
    {
        return $this->addExpressionf($this->where, $format, $values, ChainEnum::Or);
    }

    public function where(string $sql, array $values = []): static
    {
        return $this->addRawCondition($this->where, $sql, $values, ChainEnum::And);
    }

    public function orWhereEquals(string|array $column, null|bool|int|float|string|DateTimeInterface|SelectQuery|Sql $value, bool $cast = false): static
    {
        return $this->equals($this->where, $column, $value, $cast, ChainEnum::Or);
    }

    public function orWhereNotEquals(string|array $column, null|bool|int|float|string|DateTimeInterface|SelectQuery|Sql $value, bool $cast = false): static
    {
        return $this->notEquals($this->where, $column, $value, $cast, ChainEnum::Or);
    }

    public function orWhereIsNull(string|array $column): static
    {
        return $this->isNull($this->where, $column, ChainEnum::Or);
    }

    public function orWhereIsNotNull(string|array $column): static
    {
        return $this->isNotNull($this->where, $column, ChainEnum::Or);
    }

    public function orWhereLike(string|array $column, string $value, bool $caseInsensitive = false): static
    {
        return $this->like($this->where, $column, $value, $caseInsensitive, ChainEnum::Or);
    }

    public function orWhereNotLike(string|array $column, string $value, bool $caseInsensitive = false): static
    {
        return $this->notLike($this->where, $column, $value, $caseInsensitive, ChainEnum::Or);
    }

    public function orWhereStartsWith(string|array $column, string $value, bool $caseInsensitive = false, bool $escapeBackslash = false): static
    {
        return $this->startsWith($this->where, $column, $value, $caseInsensitive, $escapeBackslash, ChainEnum::Or);
    }

    public function orWhereEndsWith(string|array $column, string $value, bool $caseInsensitive = false, bool $escapeBackslash = false): static
    {
        return $this->endsWith($this->where, $column, $value, $caseInsensitive, $escapeBackslash, ChainEnum::Or);
    }

    public function orWhereContains(string|array $column, string $value, bool $caseInsensitive = false, bool $escapeBackslash = false): static
    {
        return $this->contains($this->where, $column, $value, $caseInsensitive, $escapeBackslash, ChainEnum::Or);
    }

    public function orWhereNotContains(string|array $column, string $value, bool $caseInsensitive = false, bool $escapeBackslash = false): static
    {
        return $this->notContains($this->where, $column, $value, $caseInsensitive, $escapeBackslash, ChainEnum::Or);
    }

    public function orWhereIn(string|array $column, array|SelectQuery $values): static
    {
        return $this->in($this->where, $column, $values, ChainEnum::Or);
    }

    public function orWhereNotIn(string|array $column, array|SelectQuery $values): static
    {
        return $this->notIn($this->where, $column, $values, ChainEnum::Or);
    }

    public function orWhereLessThan(string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $value): static
    {
        return $this->lessThan($this->where, $column, $value, ChainEnum::Or);
    }

    public function orWhereLessThanOrEquals(string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $value): static
    {
        return $this->lessThanOrEquals($this->where, $column, $value, ChainEnum::Or);
    }

    public function orWhereGreaterThan(string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $value): static
    {
        return $this->greaterThan($this->where, $column, $value, ChainEnum::Or);
    }

    public function orWhereGreaterThanOrEquals(string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $value): static
    {
        return $this->greaterThanOrEquals($this->where, $column, $value, ChainEnum::Or);
    }

    public function orWhereBetween(string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $min, int|float|string|DateTimeInterface|SelectQuery|Sql $max): static
    {
        return $this->between($this->where, $column, $min, $max, ChainEnum::Or);
    }

    public function orWhereNotBetween(string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $min, int|float|string|DateTimeInterface|SelectQuery|Sql $max): static
    {
        return $this->notBetween($this->where, $column, $min, $max, ChainEnum::Or);
    }

    public function orWhereEmpty(string|array $column): static
    {
        return $this->empty($this->where, $column, ChainEnum::And);
    }

    public function orWhereNotEmpty(string|array $column): static
    {
        return $this->notEmpty($this->where, $column, ChainEnum::And);
    }

    public function orWhereRegex(string|array $column, string $pattern, string $flags = ''): static
    {
        return $this->regex($this->where, $column, $pattern, $flags, ChainEnum::Or);
    }

    public function orWhereNotRegex(string|array $column, string $pattern, string $flags = ''): static
    {
        return $this->notRegex($this->where, $column, $pattern, $flags, ChainEnum::Or);
    }

    public function orWhereExists(SelectQuery $selectQuery): static
    {
        return $this->exists($this->where, $selectQuery, ChainEnum::Or);
    }

    public function orWhereNotExists(SelectQuery $selectQuery): static
    {
        return $this->notExists($this->where, $selectQuery, ChainEnum::Or);
    }

    public function orWhereGroup(callable $callback): static
    {
        return $this->group($this->where, $callback, false, WhereGroup::class, ChainEnum::Or);
    }

    public function orWhereNotGroup(callable $callback): static
    {
        return $this->group($this->where, $callback, true, WhereGroup::class, ChainEnum::Or);
    }

    public function orWhereOperator(string|array $column, string|BackedEnum $operator, null|bool|int|float|string|array|DateTimeInterface|SelectQuery|Sql $value): static
    {
        return $this->operator($this->where, $column, $operator, $value, ChainEnum::Or);
    }

    public function orWheref(string $format, null|bool|int|float|string|DateTimeInterface|SelectQuery|Sql ...$values): static
    {
        return $this->addExpressionf($this->where, $format, $values, ChainEnum::Or);
    }

    public function orWhere(string $sql, array $values = []): static
    {
        return $this->addRawCondition($this->where, $sql, $values, ChainEnum::Or);
    }
}
