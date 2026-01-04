<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Databases\DatabaseInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Queries\Traits\AltersTrait;
use Sentience\Database\Results\ResultInterface;

class AlterTableQuery extends Query
{
    use AltersTrait;

    public function __construct(DatabaseInterface $database, DialectInterface $dialect, string|array|Raw $table)
    {
        parent::__construct($database, $dialect, $table);
    }

    public function toQueryWithParams(): array
    {
        return $this->dialect->alterTable(
            $this->table,
            $this->alters
        );
    }

    public function toSql(): array
    {
        $queriesWithParams = $this->toQueryWithParams();

        return array_map(
            fn (QueryWithParams $queryWithParams): string => $queryWithParams->toSql($this->dialect),
            $queriesWithParams
        );
    }

    public function execute(bool $emulatePrepares = false): array
    {
        $queriesWithParams = $this->toQueryWithParams();

        return array_map(
            fn (QueryWithParams $queryWithParams): ResultInterface => $this->database->queryWithParams(
                $queryWithParams,
                $emulatePrepares
            ),
            $queriesWithParams
        );
    }
}
