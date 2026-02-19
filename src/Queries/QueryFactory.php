<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Databases\DatabaseInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Objects\ConditionGroup;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Results\Result;
use Sentience\Database\Results\ResultInterface;

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

    public function selectOrInsert(array $columns, array $values, ?string $lastInsertId = null, bool $emulatePrepare = false): ResultInterface
    {
        $result = $this->select()
            ->whereGroup(function (ConditionGroup $conditionGroup) use ($columns, $values): ConditionGroup {
                foreach ($columns as $column) {
                    $conditionGroup->whereEquals($column, $values[$column]);
                }

                return $conditionGroup;
            })
            ->execute($emulatePrepare);

        $rows = $result->fetchAssocs();

        if (count($rows) > 0) {
            return new Result($result->columns(), $rows);
        }

        $insertQuery = $this->insert($values)->returning();

        if ($lastInsertId) {
            $insertQuery->lastInsertId($lastInsertId);
        }

        return $insertQuery->execute($emulatePrepare);
    }

    public function insertOrIgnore(array $columns, array $values, ?string $lastInsertId = null, bool $emulatePrepare = false): ResultInterface
    {
        return $this->selectOrInsert($columns, $values, $lastInsertId, $emulatePrepare);
    }

    public function insertOrUpdate(array $columns, array $values, ?string $lastInsertId = null, bool $emulatePrepare = false): ResultInterface
    {
        $exists = $this->select()
            ->whereGroup(function (ConditionGroup $conditionGroup) use ($columns, $values): ConditionGroup {
                foreach ($columns as $column) {
                    $conditionGroup->whereEquals($column, $values[$column]);
                }

                return $conditionGroup;
            })
            ->count(null, $emulatePrepare) > 0;

        if ($exists) {
            return $this->update($values)
                ->whereGroup(function (ConditionGroup $conditionGroup) use ($columns, $values): ConditionGroup {
                    foreach ($columns as $column) {
                        $conditionGroup->whereEquals($column, $values[$column]);
                    }

                    return $conditionGroup;
                })
                ->returning()
                ->execute($emulatePrepare);
        }

        $insertQuery = $this->insert($values)->returning();

        if ($lastInsertId) {
            $insertQuery->lastInsertId($lastInsertId);
        }

        return $insertQuery->execute();
    }

    public function update(array $values): UpdateQuery
    {
        return $this->database->update($this->table)->values($values);
    }

    public function delete(): DeleteQuery
    {
        return $this->database->delete($this->table);
    }

    public function create(?callable $create = null): CreateTableQuery
    {
        $query = $this->database->createTable($this->table);

        if ($create) {
            $create($query);
        }

        return $query;
    }

    public function createIfNotExists(?callable $create = null): CreateTableQuery
    {
        return $this->create($create)->ifNotExists();
    }

    public function alter(?callable $alter = null): AlterTableQuery
    {
        $query = $this->database->alterTable($this->table);

        if ($alter) {
            $alter($query);
        }

        return $query;
    }

    public function drop(): DropTableQuery
    {
        return $this->database->dropTable($this->table);
    }

    public function dropIfExists(): DropTableQuery
    {
        return $this->drop()->ifExists();
    }
}
