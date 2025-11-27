<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Queries\Traits\ColumnsTrait;
use Sentience\Database\Queries\Traits\DistinctTrait;
use Sentience\Database\Queries\Traits\GroupByTrait;
use Sentience\Database\Queries\Traits\HavingTrait;
use Sentience\Database\Queries\Traits\JoinsTrait;
use Sentience\Database\Queries\Traits\LimitTrait;
use Sentience\Database\Queries\Traits\OffsetTrait;
use Sentience\Database\Queries\Traits\OrderByTrait;
use Sentience\Database\Queries\Traits\WhereTrait;
use Sentience\Database\Results\ResultInterface;

class SelectQuery extends Query
{
    use ColumnsTrait;
    use DistinctTrait;
    use GroupByTrait;
    use HavingTrait;
    use JoinsTrait;
    use LimitTrait;
    use OffsetTrait;
    use OrderByTrait;
    use WhereTrait;

    public function toQueryWithParams(): QueryWithParams
    {
        return $this->dialect->select(
            $this->distinct,
            $this->columns,
            $this->table,
            $this->joins,
            $this->where,
            $this->groupBy,
            $this->having,
            $this->orderBy,
            $this->limit,
            $this->offset
        );
    }

    public function toSql(): string
    {
        return parent::toSql();
    }

    public function execute(bool $emulatePrepare = false): ResultInterface
    {
        return parent::execute($emulatePrepare);
    }

    public function count(null|string|array|Raw $column = null, bool $emulatePrepare = false): int
    {
        $previousDistinct = $this->distinct;
        $previousColumns = $this->columns;
        $previousOrderBy = $this->orderBy;

        $this->distinct = false;
        $this->columns = [
            Query::alias(
                Query::raw(
                    sprintf(
                        'COUNT(%s)',
                        !is_null($column)
                        ? ($previousDistinct ? 'DISTINCT ' : '') . $this->dialect->escapeIdentifier($column)
                        : '*'
                    )
                ),
                'count'
            )
        ];
        $this->orderBy = [];

        $count = (int) $this->execute($emulatePrepare)->fetchObject()?->count ?? 0;

        $this->distinct = $previousDistinct;
        $this->columns = $previousColumns;
        $this->orderBy = $previousOrderBy;

        return $count;
    }
}
