<?php

namespace Sentience\Database\Queries\Traits;

use BackedEnum;
use DateTimeInterface;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use Sentience\Database\Queries\Enums\ChainEnum;
use Sentience\Database\Queries\Enums\ConditionEnum;
use Sentience\Database\Queries\Interfaces\ConditionGroup;
use Sentience\Database\Queries\Interfaces\Sql;
use Sentience\Database\Queries\Objects\Condition;
use Sentience\Database\Queries\Objects\RawCondition;
use Sentience\Database\Queries\Objects\WhereGroup;
use Sentience\Database\Queries\Query;
use Sentience\Database\Queries\SelectQuery;

trait ConditionsTrait
{
    protected function equals(array &$conditions, string|array $column, null|bool|int|float|string|DateTimeInterface|SelectQuery|Sql $value, bool $cast, ChainEnum $chain): static
    {
        return $this->addCondition($conditions, ConditionEnum::EQUALS, $column, [$value, $cast], $chain);
    }

    protected function notEquals(array &$conditions, string|array $column, null|bool|int|float|string|DateTimeInterface|SelectQuery|Sql $value, bool $cast, ChainEnum $chain): static
    {
        return $this->addCondition($conditions, ConditionEnum::NOT_EQUALS, $column, [$value, $cast], $chain);
    }

    protected function isNull(array &$conditions, string|array $column, ChainEnum $chain): static
    {
        return $this->addCondition($conditions, ConditionEnum::EQUALS, $column, null, $chain);
    }

    protected function isNotNull(array &$conditions, string|array $column, ChainEnum $chain): static
    {
        return $this->addCondition($conditions, ConditionEnum::NOT_EQUALS, $column, null, $chain);
    }

    protected function like(array &$conditions, string|array $column, string $value, bool $caseInsensitive, ChainEnum $chain): static
    {
        return $this->addCondition($conditions, ConditionEnum::LIKE, $column, [$value, $caseInsensitive], $chain);
    }

    protected function notLike(array &$conditions, string|array $column, string $value, bool $caseInsensitive, ChainEnum $chain): static
    {
        return $this->addCondition($conditions, ConditionEnum::NOT_LIKE, $column, [$value, $caseInsensitive], $chain);
    }

    protected function startsWith(array &$conditions, string|array $column, string $value, bool $caseInsensitive, bool $escapeBackslash, ChainEnum $chain): static
    {
        return $this->like($conditions, $column, Query::escapeLikeChars($value, $escapeBackslash) . '%', $caseInsensitive, $chain);
    }

    protected function endsWith(array &$conditions, string|array $column, string $value, bool $caseInsensitive, bool $escapeBackslash, ChainEnum $chain): static
    {
        return $this->like($conditions, $column, '%' . Query::escapeLikeChars($value, $escapeBackslash), $caseInsensitive, $chain);
    }

    protected function contains(array &$conditions, string|array $column, string $value, bool $caseInsensitive, bool $escapeBackslash, ChainEnum $chain): static
    {
        return $this->like($conditions, $column, '%' . Query::escapeLikeChars($value, $escapeBackslash) . '%', $caseInsensitive, $chain);
    }

    protected function notContains(array &$conditions, string|array $column, string $value, bool $caseInsensitive, bool $escapeBackslash, ChainEnum $chain): static
    {
        return $this->notLike($conditions, $column, '%' . Query::escapeLikeChars($value, $escapeBackslash) . '%', $caseInsensitive, $chain);
    }

    protected function in(array &$conditions, string|array $column, array|SelectQuery $values, ChainEnum $chain): static
    {
        return $this->addCondition($conditions, ConditionEnum::IN, $column, $values, $chain);
    }

    protected function notIn(array &$conditions, string|array $column, array|SelectQuery $values, ChainEnum $chain): static
    {
        return $this->addCondition($conditions, ConditionEnum::NOT_IN, $column, $values, $chain);
    }

    protected function lessThan(array &$conditions, string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $value, ChainEnum $chain): static
    {
        return $this->addCondition($conditions, ConditionEnum::LESS_THAN, $column, $value, $chain);
    }

    protected function lessThanOrEquals(array &$conditions, string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $value, ChainEnum $chain): static
    {
        return $this->addCondition($conditions, ConditionEnum::LESS_THAN_OR_EQUALS, $column, $value, $chain);
    }

    protected function greaterThan(array &$conditions, string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $value, ChainEnum $chain): static
    {
        return $this->addCondition($conditions, ConditionEnum::GREATER_THAN, $column, $value, $chain);
    }

    protected function greaterThanOrEquals(array &$conditions, string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $value, ChainEnum $chain): static
    {
        return $this->addCondition($conditions, ConditionEnum::GREATER_THAN_OR_EQUALS, $column, $value, $chain);
    }

    protected function between(array &$conditions, string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $min, int|float|string|DateTimeInterface|SelectQuery|Sql $max, ChainEnum $chain): static
    {
        return $this->addCondition($conditions, ConditionEnum::BETWEEN, $column, [$min, $max], $chain);
    }

    protected function notBetween(array &$conditions, string|array $column, int|float|string|DateTimeInterface|SelectQuery|Sql $min, int|float|string|DateTimeInterface|SelectQuery|Sql $max, ChainEnum $chain): static
    {
        return $this->addCondition($conditions, ConditionEnum::NOT_BETWEEN, $column, [$min, $max], $chain);
    }

    protected function empty(array &$conditions, string|array $column, ChainEnum $chain): static
    {
        return $this->group(
            $conditions,
            fn (WhereGroup $conditionGroup): WhereGroup => $conditionGroup
                ->whereIsNull($column)
                ->orWhereEquals($column, 0)
                ->orWhereEquals($column, '', true),
            WhereGroup::class,
            $chain
        );
    }

    protected function notEmpty(array &$conditions, string|array $column, ChainEnum $chain): static
    {
        return $this->group(
            $conditions,
            fn (WhereGroup $conditionGroup): WhereGroup => $conditionGroup
                ->whereIsNotNull($column)
                ->whereNotEquals($column, 0)
                ->whereNotEquals($column, '', true),
            WhereGroup::class,
            $chain
        );
    }

    protected function regex(array &$conditions, string|array $column, string $pattern, string $flags, ChainEnum $chain): static
    {
        return $this->addCondition($conditions, ConditionEnum::REGEX, $column, [$pattern, $flags], $chain);
    }

    protected function notRegex(array &$conditions, string|array $column, string $pattern, string $flags, ChainEnum $chain): static
    {
        return $this->addCondition($conditions, ConditionEnum::NOT_REGEX, $column, [$pattern, $flags], $chain);
    }

    protected function exists(array &$conditions, SelectQuery $selectQuery, ChainEnum $chain): static
    {
        return $this->addCondition($conditions, ConditionEnum::EXISTS, null, $selectQuery, $chain);
    }

    protected function notExists(array &$conditions, SelectQuery $selectQuery, ChainEnum $chain): static
    {
        return $this->addCondition($conditions, ConditionEnum::NOT_EXISTS, null, $selectQuery, $chain);
    }

    protected function group(array &$conditions, callable $callback, string $conditionGroup, ChainEnum $chain): static
    {
        $group = (function () use ($callback, $conditionGroup, $chain): ConditionGroup {
            $reflectionFunction = new ReflectionFunction($callback);

            $reflectionType = ($reflectionFunction->getParameters()[0] ?? null)?->getType();

            if (!$reflectionType) {
                return new $conditionGroup($chain);
            }

            $type = function (ReflectionType $reflectionType) use (&$type): ?string {
                if ($reflectionType instanceof ReflectionNamedType) {
                    $class = $reflectionType->getName();

                    if (!is_subclass_of($class, ConditionGroup::class)) {
                        return null;
                    }

                    if ($class == ConditionGroup::class) {
                        return null;
                    }

                    return $class;
                }

                if ($reflectionType instanceof ReflectionUnionType) {
                    foreach ($reflectionType->getTypes() as $reflectionType) {
                        $class = $type($reflectionType);

                        if (!$class) {
                            continue;
                        }

                        return $class;
                    }
                }

                return null;
            };

            $class = $type($reflectionType);

            if (!$class) {
                return new $conditionGroup($chain);
            }

            return new $class($chain);
        })();

        $group = $callback($group) ?? $group;

        if (!($group instanceof ConditionGroup)) {
            return $this;
        }

        if (count($group->getConditions()) == 0) {
            return $this;
        }

        return $this->addConditionGroup($conditions, $group);
    }

    protected function operator(array &$conditions, string|array $column, string|BackedEnum $operator, null|bool|int|float|string|array|DateTimeInterface|SelectQuery|Sql $value, ChainEnum $chain): static
    {
        return $this->addCondition($conditions, $operator, $column, $value, $chain);
    }

    protected function addCondition(array &$conditions, string|BackedEnum $condition, null|string|array $identifier, mixed $value, ChainEnum $chain): static
    {
        $conditions[] = new Condition($condition, $identifier, $value, $chain);

        return $this;
    }

    protected function addConditionGroup(array &$conditions, ConditionGroup $conditionGroup): static
    {
        $conditions[] = $conditionGroup;

        return $this;
    }

    protected function addExpressionf(array &$conditions, string $format, array $values, ChainEnum $chain): static
    {
        $conditions[] = new Condition(
            ConditionEnum::RAW,
            null,
            Query::expressionf($format, ...$values),
            $chain
        );

        return $this;
    }

    protected function addRawCondition(array &$conditions, string $sql, array $values, ChainEnum $chain): static
    {
        $rawCondition = new RawCondition($sql, $values, $chain);

        $conditions[] = $rawCondition->toCondition();

        return $this;
    }
}
