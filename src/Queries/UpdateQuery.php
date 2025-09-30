<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Traits\ReturningTrait;
use Sentience\Database\Queries\Traits\ValuesTrait;
use Sentience\Database\Queries\Traits\WhereTrait;
use Sentience\Database\Results\ResultInterface;

class UpdateQuery extends Query
{
    use ReturningTrait;
    use ValuesTrait;
    use WhereTrait;

    public function toQueryWithParams(): QueryWithParams
    {
        return $this->dialect->update(
            $this->table,
            $this->values,
            $this->where,
            $this->returning
        );
    }

    public function toRawQuery(): string
    {
        return parent::toRawQuery();
    }

    public function execute(): ResultInterface
    {
        return parent::execute();
    }
}
