<?php

namespace Sentience\Database\Queries\Traits;

use DateTimeInterface;
use Sentience\Database\Queries\Enums\JoinEnum;
use Sentience\Database\Queries\Interfaces\Sql;
use Sentience\Database\Queries\Objects\Alias;
use Sentience\Database\Queries\Objects\Join;
use Sentience\Database\Queries\Objects\SubQuery;
use Sentience\Database\Queries\Query;
use Sentience\Database\Queries\SelectQuery;

trait JoinsTrait
{
    protected array $joins = [];

    public function leftJoin(string|array|Alias|Sql|SubQuery $table, null|string|callable $on = null): static
    {
        return $this->addJoin(JoinEnum::LEFT_JOIN, false, $table, $on);
    }

    public function leftJoinTable(string|array|Sql $table, null|string|callable $on = null, ?string $alias = null): static
    {
        return $this->leftJoin($alias ? Query::alias($table, $alias) : $table, $on);
    }

    public function leftJoinSubQuery(SelectQuery $selectQuery, string $alias, null|string|callable $on = null): static
    {
        return $this->leftJoin(Query::subQuery($selectQuery, $alias), $on);
    }

    public function leftJoinLateral(string|array|Alias|Sql|SubQuery $table, null|string|callable $on = null): static
    {
        return $this->addJoin(JoinEnum::LEFT_JOIN, true, $table, $on);
    }

    public function leftJoinLateralTable(string|array|Sql $table, null|string|callable $on = null, ?string $alias = null): static
    {
        return $this->leftJoinLateral($alias ? Query::alias($table, $alias) : $table, $on);
    }

    public function leftJoinLateralSubQuery(SelectQuery $selectQuery, string $alias, null|string|callable $on = null): static
    {
        return $this->leftJoinLateral(Query::subQuery($selectQuery, $alias), $on);
    }

    public function innerJoin(string|array|Alias|Sql|SubQuery $table, null|string|callable $on = null): static
    {
        return $this->addJoin(JoinEnum::INNER_JOIN, false, $table, $on);
    }

    public function innerJoinTable(string|array|Sql $table, null|string|callable $on = null, ?string $alias = null): static
    {
        return $this->innerJoin($alias ? Query::alias($table, $alias) : $table, $on);
    }

    public function innerJoinSubQuery(SelectQuery $selectQuery, string $alias, null|string|callable $on = null): static
    {
        return $this->innerJoin(Query::subQuery($selectQuery, $alias), $on);
    }

    public function innerJoinLateral(string|array|Alias|Sql|SubQuery $table, null|string|callable $on = null): static
    {
        return $this->addJoin(JoinEnum::INNER_JOIN, true, $table, $on);
    }

    public function innerJoinLateralTable(string|array|Sql $table, null|string|callable $on = null, ?string $alias = null): static
    {
        return $this->innerJoinLateral($alias ? Query::alias($table, $alias) : $table, $on);
    }

    public function innerJoinLateralSubQuery(SelectQuery $selectQuery, string $alias, null|string|callable $on = null): static
    {
        return $this->innerJoinLateral(Query::subQuery($selectQuery, $alias), $on);
    }

    public function joinf(string $format, null|bool|int|float|string|DateTimeInterface|SelectQuery|Sql ...$values): static
    {
        $this->joins[] = Query::expressionf($format, ...$values);

        return $this;
    }

    public function join(string $join): static
    {
        $this->joins[] = Query::raw($join);

        return $this;
    }

    protected function addJoin(JoinEnum $join, bool $lateral, string|array|Alias|Sql|SubQuery $table, null|string|callable $on): static
    {
        $join = new Join($join, $lateral, $table);

        if (is_callable($on)) {
            $join = $on($join) ?? $join;
        } elseif (is_string($on)) {
            match (true) {
                (bool) preg_match('/^\s*([a-zA-Z0-9\-\_\.]+)\s*([\=\<\>\!]+)\s*([a-zA-Z0-9\-\_\.]+)\s*$/m', $on, $match) => $join->whereOperator(
                    explode('.', $match[1]),
                    $match[2],
                    explode('.', $match[3])
                ),
                default => $join->where($on)
            };
        }

        if (!($join instanceof Join)) {
            return $this;
        }

        $this->joins[] = $join;

        return $this;
    }
}
