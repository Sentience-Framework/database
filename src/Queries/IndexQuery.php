<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Databases\DatabaseInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Interfaces\Sql;
use Sentience\Database\Results\ResultInterface;

abstract class IndexQuery extends TableQuery
{
    public function __construct(DatabaseInterface $database, DialectInterface $dialect, string|array|Sql $table, protected string $name)
    {
        parent::__construct($database, $dialect, $table);
    }

    public function toSql(): string
    {
        return parent::toSql();
    }

    public function execute(bool $emulatePrepare = false): ResultInterface
    {
        return parent::execute($emulatePrepare);
    }

    public function explain(bool $emulatePrepare = false): array
    {
        return [];
    }

    protected function indexExists(): bool
    {
        $tables = $this->database->informationSchemaTables();

        foreach (is_string($this->table) ? array_unique([$this->table, ...$tables]) : $tables as $table) {
            $indexes = $this->database->informationSchemaIndexes($table);

            foreach ($indexes as $index) {
                if ($this->name != $index->name) {
                    continue;
                }

                return true;
            }
        }

        return false;
    }
}
