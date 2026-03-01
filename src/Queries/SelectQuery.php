<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Databases\DatabaseInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Interfaces\Sql;
use Sentience\Database\Queries\Objects\Alias;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Objects\SubQuery;
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

    public function __construct(DatabaseInterface $database, DialectInterface $dialect, string|array|Alias|Sql|SubQuery $table, array $whereMacros)
    {
        parent::__construct($database, $dialect, $table);

        $this->whereMacros = $whereMacros;
    }

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

    public function count(null|string|array|Sql $column = null, bool $emulatePrepare = false): int
    {
        $queryWithParams = $this->dialect->select(
            false,
            [
                Query::alias(
                    Query::raw(
                        sprintf(
                            'COUNT(%s)',
                            !is_null($column)
                            ? ($this->distinct ? 'DISTINCT ' : '') . $this->dialect->escapeIdentifier($column)
                            : '*'
                        )
                    ),
                    'count'
                )
            ],
            $this->table,
            $this->joins,
            $this->where,
            $this->groupBy,
            $this->having,
            [],
            $this->limit,
            $this->offset
        );

        return (int) $this->database
            ->queryWithParams($queryWithParams, $emulatePrepare)
            ->scalar() ?? 0;
    }
}
