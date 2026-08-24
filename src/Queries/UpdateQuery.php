<?php

namespace Sentience\Database\Queries;

use Sentience\Database\DatabaseInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Queries\Traits\ReturningTrait;
use Sentience\Database\Queries\Traits\SetTrait;
use Sentience\Database\Queries\Traits\WhereTrait;
use Sentience\Database\Results\ResultInterface;

class UpdateQuery extends TableQuery
{
    use ReturningTrait;
    use SetTrait;
    use WhereTrait;

    public function __construct(DatabaseInterface $database, DialectInterface $dialect, string|array|Raw $table)
    {
        parent::__construct($database, $dialect, $table);
    }

    public function toQueryWithParams(): QueryWithParams
    {
        return $this->dialect->update(
            $this->table,
            $this->set,
            $this->where,
            $this->returning
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
