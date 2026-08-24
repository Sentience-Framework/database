<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Traits\EmulateIfExistsTrait;
use Sentience\Database\Queries\Traits\IfExistsTrait;
use Sentience\Database\Results\Result;
use Sentience\Database\Results\ResultInterface;

class DropIndexQuery extends IndexQuery
{
    use EmulateIfExistsTrait;
    use IfExistsTrait;

    public function toQueryWithParams(): QueryWithParams
    {
        return $this->dialect->dropIndex(
            !$this->emulateIfExists ? $this->ifExists : false,
            $this->name,
            $this->table
        );
    }

    public function execute(bool $emulatePrepare = false): ResultInterface
    {
        if (!$this->ifExists || (!$this->emulateIfExists && $this->dialect->indexExists())) {
            return parent::execute($emulatePrepare);
        }

        if (!$this->indexExists()) {
            return new Result([], []);
        }

        return parent::execute($emulatePrepare);
    }
}
