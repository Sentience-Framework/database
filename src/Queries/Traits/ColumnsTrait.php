<?php

namespace Sentience\Database\Queries\Traits;

use Sentience\Database\Queries\Interfaces\Sql;
use Sentience\Database\Queries\Objects\Alias;
use Sentience\Database\Queries\Objects\SubQuery;
use Sentience\Database\Queries\Query;
use Sentience\Database\Queries\SelectQuery;

trait ColumnsTrait
{
    protected array $columns = [];

    public function columns(array $columns): static
    {
        $this->columns = array_map(
            function (string|array|Alias|SelectQuery|Sql|SubQuery $column, string $alias): string|array|Alias|SelectQuery|Sql|SubQuery {
                if ($column instanceof Alias || $column instanceof SubQuery || (bool) preg_match('/^[0-9]+$/', (string) $alias)) {
                    return $column;
                }

                if ($column instanceof SelectQuery) {
                    return Query::subQuery($column, $alias);
                }

                return Query::alias($column, $alias);
            },
            $columns,
            array_keys($columns)
        );

        return $this;
    }
}
