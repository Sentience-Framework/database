<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Traits\ColumnsTrait;
use Sentience\Database\Queries\Traits\IfNotExistsTrait;
use Sentience\Database\Results\Result;
use Sentience\Database\Results\ResultInterface;

class CreateIndexQuery extends IndexQuery
{
    use ColumnsTrait;
    use IfNotExistsTrait;

    protected bool $unique = false;

    public function toQueryWithParams(): QueryWithParams
    {
        return $this->dialect->createIndex(
            $this->unique,
            $this->ifNotExists,
            $this->name,
            $this->table,
            $this->columns
        );
    }

    public function execute(bool $emulatePrepare = false): ResultInterface
    {
        if (!$this->ifNotExists || $this->dialect->indexExists()) {
            return parent::execute($emulatePrepare);
        }

        if ($this->indexExists()) {
            return new Result([], []);
        }

        return parent::execute($emulatePrepare);
    }

    public function unique(): static
    {
        $this->unique = true;

        return $this;
    }
}
