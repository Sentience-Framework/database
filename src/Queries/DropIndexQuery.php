<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Traits\IfExistsTrait;
use Sentience\Database\Results\Result;
use Sentience\Database\Results\ResultInterface;

class DropIndexQuery extends IndexQuery
{
    use IfExistsTrait;

    public function toQueryWithParams(): QueryWithParams
    {
        return $this->dialect->dropIndex(
            $this->ifExists,
            $this->name,
            $this->table
        );
    }

    public function execute(bool $emulatePrepare = false): ResultInterface
    {
        if (!$this->ifExists || $this->dialect->indexExists()) {
            return parent::execute($emulatePrepare);
        }

        if (!$this->indexExists()) {
            return new Result([], []);
        }

        return parent::execute($emulatePrepare);
    }
}
