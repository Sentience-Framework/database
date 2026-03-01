<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Databases\DatabaseInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Interfaces\Sql;
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

    public function __construct(DatabaseInterface $database, DialectInterface $dialect, string|array|Sql $table, array $whereMacros)
    {
        parent::__construct($database, $dialect, $table);

        $this->whereMacros = $whereMacros;
    }

    public function toQueryWithParams(): QueryWithParams
    {
        return $this->dialect->update(
            $this->table,
            $this->values,
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
