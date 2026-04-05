<?php

namespace Sentience\Database\Queries\Traits;

use BackedEnum;
use DateTimeInterface;
use Sentience\Database\Queries\Enums\ChainEnum;
use Sentience\Database\Queries\Interfaces\Sql;
use Sentience\Database\Queries\Objects\HavingGroup;
use Sentience\Database\Queries\SelectQuery;

trait HavingTrait
{
    use ConditionsTrait;

    protected array $having = [];

    public function havingEquals(string|array $column, null|bool|int|float|string|DateTimeInterface|SelectQuery|Sql $value, bool $cast = false): static
    {
        return $this->equals($this->having, $column, $value, $cast, ChainEnum::AND);
    }

    public function havingNotEquals(string|array $column, null|bool|int|float|string|DateTimeInterface|SelectQuery|Sql $value, bool $cast = false): static
    {
        return $this->notEquals($this->having, $column, $value, $cast, ChainEnum::AND);
    }

    public function havingIsNull(string|array $column): static
    {
        return $this->isNull($this->having, $column, ChainEnum::AND);
    }

    public function havingIsNotNull(string|array $column): static
    {
        return $this->isNotNull($this->having, $column, ChainEnum::AND);
    }

    public function havingLike(string|array $column, string $value, bool $caseInsensitive = false): static
    {
        return $this->like($this->having, $column, $value, $caseInsensitive, ChainEnum::AND);
    }

    public function havingNotLike(string|array $column, string $value, bool $caseInsensitive = false): static
    {
        return $this->notLike($this->having, $column, $value, $caseInsensitive, ChainEnum::AND);
    }

    public function havingStartsWith(string|array $column, string $value, bool $caseInsensitive = false, bool $escapeBackslash = false): static
    {
        return $this->startsWith($this->having, $column, $value, $caseInsensitive, $escapeBackslash, ChainEnum::AND);
    }

    public function havingEndsWith(string|array $column, string $value, bool $caseInsensitive = false, bool $escapeBackslash = false): static
    {
        return $this->endsWith($this->having, $column, $value, $caseInsensitive, $escapeBackslash, ChainEnum::AND);
    }

    public function havingContains(string|array $column, string $value, bool $caseInsensitive = false, bool $escapeBackslash = false): static
    {
        return $this->contains($this->having, $column, $value, $caseInsensitive, $escapeBackslash, ChainEnum::AND);
    }

    public function havingNotContains(string|array $column, string $value, bool $caseInsensitive = false, bool $escapeBackslash = false): static
    {
        return $this->notContains($this->having, $column, $value, $caseInsensitive, $escapeBackslash, ChainEnum::AND);
    }

    public function havingIn(string|array $column, array|SelectQuery $values): static
    {
        return $this->in($this->having, $column, $values, ChainEnum::AND);
    }

    public function havingNotIn(string|array $column, array|SelectQuery $values): static
    {
        return $this->notIn($this->having, $column, $values, ChainEnum::AND);
    }

    public function havingLessThan(string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $value): static
    {
        return $this->lessThan($this->having, $column, $value, ChainEnum::AND);
    }

    public function havingLessThanOrEquals(string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $value): static
    {
        return $this->lessThanOrEquals($this->having, $column, $value, ChainEnum::AND);
    }

    public function havingGreaterThan(string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $value): static
    {
        return $this->greaterThan($this->having, $column, $value, ChainEnum::AND);
    }

    public function havingGreaterThanOrEquals(string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $value): static
    {
        return $this->greaterThanOrEquals($this->having, $column, $value, ChainEnum::AND);
    }

    public function havingBetween(string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $min, int|float|string|DateTimeInterface|SelectQuery|Sql $max): static
    {
        return $this->between($this->having, $column, $min, $max, ChainEnum::AND);
    }

    public function havingNotBetween(string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $min, int|float|string|DateTimeInterface|SelectQuery|Sql $max): static
    {
        return $this->notBetween($this->having, $column, $min, $max, ChainEnum::AND);
    }

    public function havingEmpty(string|array $column): static
    {
        return $this->empty($this->having, $column, ChainEnum::AND);
    }

    public function havingNotEmpty(string|array $column): static
    {
        return $this->notEmpty($this->having, $column, ChainEnum::AND);
    }

    public function havingRegex(string|array $column, string $pattern, string $flags = ''): static
    {
        return $this->regex($this->having, $column, $pattern, $flags, ChainEnum::AND);
    }

    public function havingNotRegex(string|array $column, string $pattern, string $flags = ''): static
    {
        return $this->notRegex($this->having, $column, $pattern, $flags, ChainEnum::AND);
    }

    public function havingExists(SelectQuery $selectQuery): static
    {
        return $this->exists($this->having, $selectQuery, ChainEnum::AND);
    }

    public function havingNotExists(SelectQuery $selectQuery): static
    {
        return $this->notExists($this->having, $selectQuery, ChainEnum::AND);
    }

    public function havingGroup(callable $callback): static
    {
        return $this->group($this->having, $callback, HavingGroup::class, ChainEnum::AND);
    }

    public function havingOperator(string|array $column, string|BackedEnum $operator, null|bool|int|float|string|array|DateTimeInterface|SelectQuery|Sql $value): static
    {
        return $this->operator($this->having, $column, $operator, $value, ChainEnum::AND);
    }

    public function havingf(string $format, null|bool|int|float|string|DateTimeInterface|SelectQuery|Sql ...$values): static
    {
        return $this->addExpressionf($this->where, $format, $values, ChainEnum::OR);
    }

    public function having(string $sql, array $values = []): static
    {
        return $this->addRawCondition($this->having, $sql, $values, ChainEnum::AND);
    }

    public function orHavingEquals(string|array $column, null|bool|int|float|string|DateTimeInterface|SelectQuery|Sql $value, bool $cast = false): static
    {
        return $this->equals($this->having, $column, $value, $cast, ChainEnum::OR);
    }

    public function orHavingNotEquals(string|array $column, null|bool|int|float|string|DateTimeInterface|SelectQuery|Sql $value, bool $cast = false): static
    {
        return $this->notEquals($this->having, $column, $value, $cast, ChainEnum::OR);
    }

    public function orHavingIsNull(string|array $column): static
    {
        return $this->isNull($this->having, $column, ChainEnum::OR);
    }

    public function orHavingIsNotNull(string|array $column): static
    {
        return $this->isNotNull($this->having, $column, ChainEnum::OR);
    }

    public function orHavingLike(string|array $column, string $value, bool $caseInsensitive = false): static
    {
        return $this->like($this->having, $column, $value, $caseInsensitive, ChainEnum::OR);
    }

    public function orHavingNotLike(string|array $column, string $value, bool $caseInsensitive = false): static
    {
        return $this->notLike($this->having, $column, $value, $caseInsensitive, ChainEnum::OR);
    }

    public function orHavingStartsWith(string|array $column, string $value, bool $caseInsensitive = false, bool $escapeBackslash = false): static
    {
        return $this->startsWith($this->having, $column, $value, $caseInsensitive, $escapeBackslash, ChainEnum::OR);
    }

    public function orHavingEndsWith(string|array $column, string $value, bool $caseInsensitive = false, bool $escapeBackslash = false): static
    {
        return $this->endsWith($this->having, $column, $value, $caseInsensitive, $escapeBackslash, ChainEnum::OR);
    }

    public function orHavingContains(string|array $column, string $value, bool $caseInsensitive = false, bool $escapeBackslash = false): static
    {
        return $this->contains($this->having, $column, $value, $caseInsensitive, $escapeBackslash, ChainEnum::OR);
    }

    public function orHavingNotContains(string|array $column, string $value, bool $caseInsensitive = false, bool $escapeBackslash = false): static
    {
        return $this->notContains($this->having, $column, $value, $caseInsensitive, $escapeBackslash, ChainEnum::OR);
    }

    public function orHavingIn(string|array $column, array|SelectQuery $values): static
    {
        return $this->in($this->having, $column, $values, ChainEnum::OR);
    }

    public function orHavingNotIn(string|array $column, array|SelectQuery $values): static
    {
        return $this->notIn($this->having, $column, $values, ChainEnum::OR);
    }

    public function orHavingLessThan(string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $value): static
    {
        return $this->lessThan($this->having, $column, $value, ChainEnum::OR);
    }

    public function orHavingLessThanOrEquals(string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $value): static
    {
        return $this->lessThanOrEquals($this->having, $column, $value, ChainEnum::OR);
    }

    public function orHavingGreaterThan(string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $value): static
    {
        return $this->greaterThan($this->having, $column, $value, ChainEnum::OR);
    }

    public function orHavingGreaterThanOrEquals(string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $value): static
    {
        return $this->greaterThanOrEquals($this->having, $column, $value, ChainEnum::OR);
    }

    public function orHavingBetween(string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $min, int|float|string|DateTimeInterface|SelectQuery|Sql $max): static
    {
        return $this->between($this->having, $column, $min, $max, ChainEnum::OR);
    }

    public function orHavingNotBetween(string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $min, int|float|string|DateTimeInterface|SelectQuery|Sql $max): static
    {
        return $this->notBetween($this->having, $column, $min, $max, ChainEnum::OR);
    }

    public function orHavingEmpty(string|array $column): static
    {
        return $this->empty($this->having, $column, ChainEnum::AND);
    }

    public function orHavingNotEmpty(string|array $column): static
    {
        return $this->notEmpty($this->having, $column, ChainEnum::AND);
    }

    public function orHavingRegex(string|array $column, string $pattern, string $flags = ''): static
    {
        return $this->regex($this->having, $column, $pattern, $flags, ChainEnum::OR);
    }

    public function orHavingNotRegex(string|array $column, string $pattern, string $flags = ''): static
    {
        return $this->notRegex($this->having, $column, $pattern, $flags, ChainEnum::OR);
    }

    public function orHavingExists(SelectQuery $selectQuery): static
    {
        return $this->exists($this->having, $selectQuery, ChainEnum::OR);
    }

    public function orHavingNotExists(SelectQuery $selectQuery): static
    {
        return $this->notExists($this->having, $selectQuery, ChainEnum::OR);
    }

    public function orHavingGroup(callable $callback): static
    {
        return $this->group($this->having, $callback, HavingGroup::class, ChainEnum::OR);
    }

    public function orHavingOperator(string|array $column, string|BackedEnum $operator, null|bool|int|float|string|array|DateTimeInterface|SelectQuery|Sql $value): static
    {
        return $this->operator($this->having, $column, $operator, $value, ChainEnum::OR);
    }

    public function orHavingf(string $format, null|bool|int|float|string|DateTimeInterface|SelectQuery|Sql ...$values): static
    {
        return $this->addExpressionf($this->where, $format, $values, ChainEnum::OR);
    }

    public function orHaving(string $sql, array $values = []): static
    {
        return $this->addRawCondition($this->having, $sql, $values, ChainEnum::OR);
    }
}
