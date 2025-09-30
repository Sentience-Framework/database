<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Traits\IfExistsTrait;
use Sentience\Database\Results\ResultInterface;

class DropTableQuery extends Query
{
    use IfExistsTrait;

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
        return parent::execute($emulatePrepare);
    }
}
