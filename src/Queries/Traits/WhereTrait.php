<?php

namespace Sentience\Database\Queries\Traits;

use BackedEnum;
use DateTimeInterface;
use Sentience\Database\Queries\Enums\ChainEnum;
use Sentience\Database\Queries\Enums\ConditionEnum;
use Sentience\Database\Queries\Objects\Condition;
use Sentience\Database\Queries\Objects\ConditionGroup;
use Sentience\Database\Queries\Objects\Identifier;
use Sentience\Database\Queries\Objects\RawCondition;
use Sentience\Database\Queries\Query;
use Sentience\Database\Queries\SelectQuery;

trait WhereTrait
{
    protected array $where = [];

    public function whereEquals(string|array $column, mixed $value): static
    {
        return $this->equals($column, $value, ChainEnum::AND);
    }

    public function whereNotEquals(string|array $column, mixed $value): static
    {
        return $this->notEquals($column, $value, ChainEnum::AND);
    }

    public function whereIsNull(string|array $column): static
    {
        return $this->isNull($column, ChainEnum::AND);
    }

    public function whereIsNotNull(string|array $column): static
    {
        return $this->isNotNull($column, ChainEnum::AND);
    }

    public function whereLike(string|array $column, string $value): static
    {
        return $this->like($column, $value, ChainEnum::AND);
    }

    public function whereNotLike(string|array $column, string $value): static
    {
        return $this->notLike($column, $value, ChainEnum::AND);
    }

    public function whereStartsWith(string|array $column, string $value, bool $escapeBackslash = false): static
    {
        return $this->startsWith($column, $value, $escapeBackslash, ChainEnum::AND);
    }

    public function whereEndsWith(string|array $column, string $value, bool $escapeBackslash = false): static
    {
        return $this->endsWith($column, $value, $escapeBackslash, ChainEnum::AND);
    }

    public function whereContains(string|array $column, string $value, bool $escapeBackslash = false): static
    {
        return $this->contains($column, $value, $escapeBackslash, ChainEnum::AND);
    }

    public function whereNotContains(string|array $column, string $value, bool $escapeBackslash = false): static
    {
        return $this->notContains($column, $value, $escapeBackslash, ChainEnum::AND);
    }

    public function whereIn(string|array $column, array|SelectQuery $values): static
    {
        return $this->in($column, $values, ChainEnum::AND);
    }

    public function whereNotIn(string|array $column, array|SelectQuery $values): static
    {
        return $this->notIn($column, $values, ChainEnum::AND);
    }

    public function whereLessThan(string|array $column, int|float|string|DateTimeInterface|Identifier|SelectQuery $value): static
    {
        return $this->lessThan($column, $value, ChainEnum::AND);
    }

    public function whereLessThanOrEquals(string|array $column, int|float|string|DateTimeInterface|Identifier|SelectQuery $value): static
    {
        return $this->lessThanOrEquals($column, $value, ChainEnum::AND);
    }

    public function whereGreaterThan(string|array $column, int|float|string|DateTimeInterface|Identifier|SelectQuery $value): static
    {
        return $this->greaterThan($column, $value, ChainEnum::AND);
    }

    public function whereGreaterThanOrEquals(string|array $column, int|float|string|DateTimeInterface|Identifier|SelectQuery $value): static
    {
        return $this->greaterThanOrEquals($column, $value, ChainEnum::AND);
    }

    public function whereBetween(string|array $column, int|float|string|DateTimeInterface|Identifier|SelectQuery $min, int|float|string|DateTimeInterface|Identifier|SelectQuery $max): static
    {
        return $this->between($column, $min, $max, ChainEnum::AND);
    }

    public function whereNotBetween(string|array $column, int|float|string|DateTimeInterface|Identifier|SelectQuery $min, int|float|string|DateTimeInterface|Identifier|SelectQuery $max): static
    {
        return $this->notBetween($column, $min, $max, ChainEnum::AND);
    }

    public function whereEmpty(string|array $column): static
    {
        return $this->empty($column, ChainEnum::AND);
    }

    public function whereNotEmpty(string|array $column): static
    {
        return $this->notEmpty($column, ChainEnum::AND);
    }

    public function whereRegex(string|array $column, string $pattern): static
    {
        return $this->regex($column, $pattern, ChainEnum::AND);
    }

    public function whereNotRegex(string|array $column, string $pattern): static
    {
        return $this->notRegex($column, $pattern, ChainEnum::AND);
    }

    public function whereExists(SelectQuery $selectQuery): static
    {
        return $this->exists($selectQuery, ChainEnum::AND);
    }

    public function whereNotExists(SelectQuery $selectQuery): static
    {
        return $this->notExists($selectQuery, ChainEnum::AND);
    }

    public function whereGroup(callable $callback): static
    {
        return $this->group($callback, ChainEnum::AND);
    }

    public function whereOperator(string|array $column, string|BackedEnum $operator, mixed $value): static
    {
        return $this->operator($column, $operator, $value, ChainEnum::AND);
    }

    public function where(string $sql, array $values = []): static
    {
        return $this->addRawCondition($sql, $values, ChainEnum::AND);
    }

    public function orWhereEquals(string|array $column, mixed $value): static
    {
        return $this->equals($column, $value, ChainEnum::OR);
    }

    public function orWhereNotEquals(string|array $column, mixed $value): static
    {
        return $this->notEquals($column, $value, ChainEnum::OR);
    }

    public function orWhereIsNull(string|array $column): static
    {
        return $this->isNull($column, ChainEnum::OR);
    }

    public function orWhereIsNotNull(string|array $column): static
    {
        return $this->isNotNull($column, ChainEnum::OR);
    }

    public function orWhereLike(string|array $column, string $value): static
    {
        return $this->like($column, $value, ChainEnum::OR);
    }

    public function orWhereNotLike(string|array $column, string $value): static
    {
        return $this->notLike($column, $value, ChainEnum::OR);
    }

    public function orWhereStartsWith(string|array $column, string $value, bool $escapeBackslash = false): static
    {
        return $this->startsWith($column, $value, $escapeBackslash, ChainEnum::OR);
    }

    public function orWhereEndsWith(string|array $column, string $value, bool $escapeBackslash = false): static
    {
        return $this->endsWith($column, $value, $escapeBackslash, ChainEnum::OR);
    }

    public function orWhereContains(string|array $column, string $value, bool $escapeBackslash = false): static
    {
        return $this->contains($column, $value, $escapeBackslash, ChainEnum::OR);
    }

    public function orWhereNotContains(string|array $column, string $value, bool $escapeBackslash = false): static
    {
        return $this->notContains($column, $value, $escapeBackslash, ChainEnum::OR);
    }

    public function orWhereIn(string|array $column, array|SelectQuery $values): static
    {
        return $this->in($column, $values, ChainEnum::OR);
    }

    public function orWhereNotIn(string|array $column, array|SelectQuery $values): static
    {
        return $this->notIn($column, $values, ChainEnum::OR);
    }

    public function orWhereLessThan(string|array $column, int|float|string|DateTimeInterface|Identifier|SelectQuery $value): static
    {
        return $this->lessThan($column, $value, ChainEnum::OR);
    }

    public function orWhereLessThanOrEquals(string|array $column, int|float|string|DateTimeInterface|Identifier|SelectQuery $value): static
    {
        return $this->lessThanOrEquals($column, $value, ChainEnum::OR);
    }

    public function orWhereGreaterThan(string|array $column, int|float|string|DateTimeInterface|Identifier|SelectQuery $value): static
    {
        return $this->greaterThan($column, $value, ChainEnum::OR);
    }

    public function orWhereGreaterThanOrEquals(string|array $column, int|float|string|DateTimeInterface|Identifier|SelectQuery $value): static
    {
        return $this->greaterThanOrEquals($column, $value, ChainEnum::OR);
    }

    public function orWhereBetween(string|array $column, int|float|string|DateTimeInterface|Identifier|SelectQuery $min, int|float|string|DateTimeInterface|Identifier|SelectQuery $max): static
    {
        return $this->between($column, $min, $max, ChainEnum::OR);
    }

    public function orWhereNotBetween(string|array $column, int|float|string|DateTimeInterface|Identifier|SelectQuery $min, int|float|string|DateTimeInterface|Identifier|SelectQuery $max): static
    {
        return $this->notBetween($column, $min, $max, ChainEnum::OR);
    }

    public function orWhereEmpty(string|array $column): static
    {
        return $this->empty($column, ChainEnum::AND);
    }

    public function orWhereNotEmpty(string|array $column): static
    {
        return $this->notEmpty($column, ChainEnum::AND);
    }

    public function orWhereRegex(string|array $column, string $pattern): static
    {
        return $this->regex($column, $pattern, ChainEnum::OR);
    }

    public function orWhereNotRegex(string|array $column, string $pattern): static
    {
        return $this->notRegex($column, $pattern, ChainEnum::OR);
    }

    public function orWhereExists(SelectQuery $selectQuery): static
    {
        return $this->exists($selectQuery, ChainEnum::OR);
    }

    public function orWhereNotExists(SelectQuery $selectQuery): static
    {
        return $this->notExists($selectQuery, ChainEnum::OR);
    }

    public function orWhereGroup(callable $callback): static
    {
        return $this->group($callback, ChainEnum::OR);
    }

    public function orWhereOperator(string|array $column, string|BackedEnum $operator, mixed $value): static
    {
        return $this->operator($column, $operator, $value, ChainEnum::OR);
    }

    public function orWhere(string $sql, array $values = []): static
    {
        return $this->addRawCondition($sql, $values, ChainEnum::OR);
    }

    protected function equals(string|array $column, mixed $value, ChainEnum $chain): static
    {
        return $this->addCondition(ConditionEnum::EQUALS, $column, $value, $chain);
    }

    protected function notEquals(string|array $column, mixed $value, ChainEnum $chain): static
    {
        return $this->addCondition(ConditionEnum::NOT_EQUALS, $column, $value, $chain);
    }

    protected function isNull(string|array $column, ChainEnum $chain): static
    {
        return $this->addCondition(ConditionEnum::EQUALS, $column, null, $chain);
    }

    protected function isNotNull(string|array $column, ChainEnum $chain): static
    {
        return $this->addCondition(ConditionEnum::NOT_EQUALS, $column, null, $chain);
    }

    protected function like(string|array $column, string $value, ChainEnum $chain): static
    {
        return $this->addCondition(ConditionEnum::LIKE, $column, $value, $chain);
    }

    protected function notLike(string|array $column, string $value, ChainEnum $chain): static
    {
        return $this->addCondition(ConditionEnum::NOT_LIKE, $column, $value, $chain);
    }

    protected function startsWith(string|array $column, string $value, bool $escapeBackslash, ChainEnum $chain): static
    {
        return $this->like($column, Query::escapeLikeChars($value, $escapeBackslash) . '%', $chain);
    }

    protected function endsWith(string|array $column, string $value, bool $escapeBackslash, ChainEnum $chain): static
    {
        return $this->like($column, '%' . Query::escapeLikeChars($value, $escapeBackslash), $chain);
    }

    protected function contains(string|array $column, string $value, bool $escapeBackslash, ChainEnum $chain): static
    {
        return $this->like($column, '%' . Query::escapeLikeChars($value, $escapeBackslash) . '%', $chain);
    }

    protected function notContains(string|array $column, string $value, bool $escapeBackslash, ChainEnum $chain): static
    {
        return $this->notLike($column, '%' . Query::escapeLikeChars($value, $escapeBackslash) . '%', $chain);
    }

    protected function in(string|array $column, array|SelectQuery $values, ChainEnum $chain): static
    {
        return $this->addCondition(ConditionEnum::IN, $column, $values, $chain);
    }

    protected function notIn(string|array $column, array|SelectQuery $values, ChainEnum $chain): static
    {
        return $this->addCondition(ConditionEnum::NOT_IN, $column, $values, $chain);
    }

    protected function lessThan(string|array $column, int|float|string|DateTimeInterface|Identifier|SelectQuery $value, ChainEnum $chain): static
    {
        return $this->addCondition(ConditionEnum::LESS_THAN, $column, $value, $chain);
    }

    protected function lessThanOrEquals(string|array $column, int|float|string|DateTimeInterface|Identifier|SelectQuery $value, ChainEnum $chain): static
    {
        return $this->addCondition(ConditionEnum::LESS_THAN_OR_EQUALS, $column, $value, $chain);
    }

    protected function greaterThan(string|array $column, int|float|string|DateTimeInterface|Identifier|SelectQuery $value, ChainEnum $chain): static
    {
        return $this->addCondition(ConditionEnum::GREATER_THAN, $column, $value, $chain);
    }

    protected function greaterThanOrEquals(string|array $column, int|float|string|DateTimeInterface|Identifier|SelectQuery $value, ChainEnum $chain): static
    {
        return $this->addCondition(ConditionEnum::GREATER_THAN_OR_EQUALS, $column, $value, $chain);
    }

    protected function between(string|array $column, int|float|string|DateTimeInterface|Identifier|SelectQuery $min, int|float|string|DateTimeInterface|Identifier|SelectQuery $max, ChainEnum $chain): static
    {
        return $this->addCondition(ConditionEnum::BETWEEN, $column, [$min, $max], $chain);
    }

    protected function notBetween(string|array $column, int|float|string|DateTimeInterface|Identifier|SelectQuery $min, int|float|string|DateTimeInterface|Identifier|SelectQuery $max, ChainEnum $chain): static
    {
        return $this->addCondition(ConditionEnum::NOT_BETWEEN, $column, [$min, $max], $chain);
    }

    protected function empty(string|array $column, ChainEnum $chain): static
    {
        return $this->group(
            fn (ConditionGroup $conditionGroup): ConditionGroup => $conditionGroup
                ->orWhereIsNull($column)
                ->orWhereEquals($column, 0)
                ->orWhereEquals($column, ''),
            $chain
        );
    }

    protected function notEmpty(string|array $column, ChainEnum $chain): static
    {
        return $this->group(
            fn (ConditionGroup $conditionGroup): ConditionGroup => $conditionGroup
                ->whereIsNotNull($column)
                ->whereNotEquals($column, 0)
                ->whereNotEquals($column, ''),
            $chain
        );
    }

    protected function regex(string|array $column, string $pattern, ChainEnum $chain): static
    {
        return $this->addCondition(ConditionEnum::REGEX, $column, $this->parsePregPattern($pattern), $chain);
    }

    protected function notRegex(string|array $column, string $pattern, ChainEnum $chain): static
    {
        return $this->addCondition(ConditionEnum::NOT_REGEX, $column, $this->parsePregPattern($pattern), $chain);
    }

    protected function exists(SelectQuery $selectQuery, ChainEnum $chain): static
    {
        return $this->addCondition(ConditionEnum::EXISTS, null, $selectQuery, $chain);
    }

    protected function notExists(SelectQuery $selectQuery, ChainEnum $chain): static
    {
        return $this->addCondition(ConditionEnum::NOT_EXISTS, null, $selectQuery, $chain);
    }

    protected function group(callable $callback, ChainEnum $chain): static
    {
        $conditionGroup = new ConditionGroup($chain);

        $conditionGroup = $callback($conditionGroup) ?? $conditionGroup;

        if (!($conditionGroup instanceof ConditionGroup)) {
            return $this;
        }

        if (count($conditionGroup->getConditions()) == 0) {
            return $this;
        }

        return $this->addConditionGroup($conditionGroup);
    }

    protected function operator(string|array $column, string|BackedEnum $operator, mixed $value, ChainEnum $chain): static
    {
        return $this->addCondition($operator, $column, $value, $chain);
    }

    protected function addCondition(string|BackedEnum $condition, null|string|array $identifier, mixed $value, ChainEnum $chain): static
    {
        $this->where[] = new Condition($condition, $identifier, $value, $chain);

        return $this;
    }

    protected function addConditionGroup(ConditionGroup $conditionGroup): static
    {
        $this->where[] = $conditionGroup;

        return $this;
    }

    protected function addRawCondition(string $sql, array $values, ChainEnum $chain): static
    {
        $rawCondition = new RawCondition($sql, $values, $chain);

        $this->where[] = $rawCondition->toCondition();

        return $this;
    }

    protected function parsePregPattern(string $pattern): array
    {
        preg_match('/^\/(.*)\/([a-zA-Z]*)$/', $pattern, $matches);

        if (count($matches) == 0) {
            return [$pattern, ''];
        }

        return array_slice($matches, 1);
    }
}
