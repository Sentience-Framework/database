<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Traits\EmulateIfExistsTrait;
use Sentience\Database\Queries\Traits\IfExistsTrait;
use Sentience\Database\Results\Result;
use Sentience\Database\Results\ResultInterface;

class DropTableQuery extends SchemaQuery
{
    use EmulateIfExistsTrait;
    use IfExistsTrait;

    public function toQueryWithParams(): QueryWithParams
    {
        return $this->dialect->dropTable(
            !$this->emulateIfExists ? $this->ifExists : false,
            $this->table
        );
    }

    public function toSql(): string
    {
        return parent::toSql();
    }

    public function execute(bool $emulatePrepare = false): ResultInterface
    {
        if (!$this->ifExists || (!$this->emulateIfExists && $this->dialect->tableExists())) {
            return parent::execute($emulatePrepare);
        }

        if (!$this->tableExists()) {
            return new Result([], []);
        }

        return parent::execute($emulatePrepare);
    }
}
