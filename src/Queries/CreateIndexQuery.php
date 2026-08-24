<?php

namespace Sentience\Database\Queries;

use Sentience\Database\DatabaseInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Interfaces\Sql;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Traits\ColumnsTrait;
use Sentience\Database\Queries\Traits\IfNotExistsTrait;
use Sentience\Database\Results\ResultInterface;

class CreateIndexQuery extends TableQuery
{
    use ColumnsTrait;
    use IfNotExistsTrait;

    protected bool $unique = false;

    public function __construct(DatabaseInterface $database, DialectInterface $dialect, string|array|Sql $table, protected string $name)
    {
        parent::__construct($database, $dialect, $table);
    }

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

    public function toSql(): string
    {
        return parent::toSql();
    }

    public function execute(bool $emulatePrepare = false): ResultInterface
    {
        return parent::execute($emulatePrepare);
    }

    public function unique(): static
    {
        $this->unique = true;

        return $this;
    }
}
