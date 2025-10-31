<?php

namespace Sentience\Database\Queries;

use Sentience\Database\DatabaseInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Objects\Raw;

class QueryFactory
{
    public function __construct(
        protected DatabaseInterface $database,
        protected DialectInterface $dialect,
        protected string|array|Raw $table
    ) {
    }

    public function select(array $columns = []): SelectQuery
    {
        return $this->database->select($this->table)->columns($columns);
    }

    public function insert(array $values): InsertQuery
    {
        return $this->database->insert($this->table)->values($values);
    }

    public function update(array $values): UpdateQuery
    {
        return $this->database->update($this->table)->values($values);
    }

    public function delete(): DeleteQuery
    {
        return $this->database->delete($this->table);
    }

    public function create(): CreateTableQuery
    {
        return $this->database->createTable($this->table);
    }

    public function alter(): AlterTableQuery
    {
        return $this->database->alterTable($this->table);
    }

    public function drop(): DropTableQuery
    {
        return $this->database->dropTable($this->table);
    }
}
