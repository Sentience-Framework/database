<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Queries\Objects\Statement;
use Sentience\Database\Queries\Traits\AltersTrait;
use Sentience\Database\Results\ResultInterface;

class AlterTableQuery extends SchemaQuery
{
    use AltersTrait;

    public function toStatement(): array
    {
        return $this->dialect->alterTable(
            $this->table,
            $this->alters
        );
    }

    public function toSql(): array
    {
        $statements = $this->toStatement();

        return array_map(
            fn (Statement $statement): string => $statement->toSql($this->dialect),
            $statements
        );
    }

    public function execute(bool $emulatePrepares = false): array
    {
        $statements = $this->toStatement();

        return array_map(
            fn (Statement $statement): ResultInterface => $this->database->statement(
                $statement,
                $emulatePrepares
            ),
            $statements
        );
    }
}
