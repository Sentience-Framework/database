<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Query;
use Sentience\Database\Queries\SelectQuery;

class SubQuery
{
    public function __construct(
        public SelectQuery $selectQuery,
        public string $alias
    ) {
    }

    public function toAlias(callable $build): Alias
    {
        $sql = (string) $build($this->selectQuery);

        return Query::alias(
            Query::raw($sql),
            $this->alias
        );
    }
}
