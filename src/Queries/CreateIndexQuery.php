<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Traits\ColumnsTrait;
use Sentience\Database\Queries\Traits\EmulateIfNotExistsTrait;
use Sentience\Database\Queries\Traits\IfNotExistsTrait;
use Sentience\Database\Queries\Traits\WhereTrait;
use Sentience\Database\Results\Result;
use Sentience\Database\Results\ResultInterface;

class CreateIndexQuery extends IndexQuery
{
    use ColumnsTrait;
    use EmulateIfNotExistsTrait;
    use IfNotExistsTrait;
    use WhereTrait;

    protected bool $unique = false;

    public function toQueryWithParams(): QueryWithParams
    {
        return $this->dialect->createIndex(
            $this->unique,
            !$this->emulateIfNotExists ? $this->ifNotExists : false,
            $this->name,
            $this->table,
            $this->columns,
            $this->where
        );
    }

    public function execute(bool $emulatePrepare = false): ResultInterface
    {
        if (!$this->ifNotExists || (!$this->emulateIfNotExists && $this->dialect->indexExists())) {
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
