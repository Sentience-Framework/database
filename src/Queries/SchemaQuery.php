<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Databases\DatabaseInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Interfaces\Sql;

abstract class SchemaQuery extends TableQuery
{
    public function __construct(DatabaseInterface $database, DialectInterface $dialect, string|array|Sql $table)
    {
        parent::__construct($database, $dialect, $table);
    }

    public function explain(bool $emulatePrepare = false): array
    {
        return [];
    }

    protected function tableExists(): bool
    {
        $end = function (string|array|Sql $table) use (&$end): string|Sql {
            if (!is_array($table)) {
                return $table;
            }

            return $end(end($table));
        };

        $table = $end($this->table);

        return in_array(
            !is_string($table) ? $table->rawSql($this->dialect) : $table,
            $this->database->informationSchemaTables()
        );
    }
}
