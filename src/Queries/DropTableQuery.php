<?php

namespace Sentience\Database\Queries;

use Sentience\Database\DatabaseInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Interfaces\Sql;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Traits\IfExistsTrait;
use Sentience\Database\Results\Result;
use Sentience\Database\Results\ResultInterface;

class DropTableQuery extends SchemaQuery
{
    use IfExistsTrait;

    public function __construct(DatabaseInterface $database, DialectInterface $dialect, string|array|Sql $table)
    {
        parent::__construct($database, $dialect, $table);
    }

    public function toQueryWithParams(): QueryWithParams
    {
        return $this->dialect->dropTable(
            $this->ifExists,
            $this->table
        );
    }

    public function toSql(): string
    {
        return parent::toSql();
    }

    public function execute(bool $emulatePrepare = false): ResultInterface
    {
        if (!$this->ifExists || $this->dialect->tableExists()) {
            return parent::execute($emulatePrepare);
        }

        if (!$this->tableExists()) {
            return new Result([], []);
        }

        return parent::execute($emulatePrepare);
    }
}
