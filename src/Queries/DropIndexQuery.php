<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Databases\DatabaseInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Interfaces\Sql;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Traits\IfExistsTrait;
use Sentience\Database\Results\ResultInterface;

class DropIndexQuery extends TableQuery
{
    use IfExistsTrait;

    public function __construct(DatabaseInterface $database, DialectInterface $dialect, string|array|Sql $table, protected string $name)
    {
        parent::__construct($database, $dialect, $table);
    }

    public function toQueryWithParams(): QueryWithParams
    {
        return $this->dialect->dropIndex(
            $this->ifExists,
            $this->name,
            $this->table
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
}
