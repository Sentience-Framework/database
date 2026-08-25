<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Databases\DatabaseInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Interfaces\Sql;
use Sentience\Database\Queries\Objects\Alias;
use Sentience\Database\Queries\Objects\SubQuery;

abstract class TableQuery extends Query
{
    public function __construct(
        DatabaseInterface $database,
        DialectInterface $dialect,
        protected string|array|Alias|Sql|SubQuery $table
    ) {
        parent::__construct($database, $dialect);
    }
}
