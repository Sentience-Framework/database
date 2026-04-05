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

    public function leftJoin(string|array|Alias|Sql|SubQuery $table, ?callable $on = null): static
    {
        return $this->addJoin(JoinEnum::LEFT_JOIN, $table, $on);
    }

    public function leftJoinTable(string|array|Sql $table, ?callable $on = null, ?string $alias = null): static
    {
        return $this->leftJoin($alias ? Query::alias($table, $alias) : $table, $on);
    }

    public function leftJoinSubQuery(SelectQuery $selectQuery, string $alias, ?callable $on = null): static
    {
        return $this->leftJoin(Query::subQuery($selectQuery, $alias), $on);
    }

    public function innerJoin(string|array|Alias|Sql|SubQuery $table, ?callable $on = null): static
    {
        return $this->addJoin(JoinEnum::INNER_JOIN, $table, $on);
    }

    public function innerJoinTable(string|array|Sql $table, ?callable $on = null, ?string $alias = null): static
    {
        return $this->innerJoin($alias ? Query::alias($table, $alias) : $table, $on);
    }

    public function innerJoinSubQuery(SelectQuery $selectQuery, string $alias, ?callable $on = null): static
    {
        return $this->innerJoin(Query::subQuery($selectQuery, $alias), $on);
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

    protected function addJoin(JoinEnum $join, string|array|Alias|Sql|SubQuery $table, ?callable $on): static
    {
        $join = new Join($join, $table);

        if ($on) {
            $join = $on($join) ?? $join;
        }

        if (!($join instanceof Join)) {
            return $this;
        }

        $this->joins[] = $join;

        return $this;
    }
}
