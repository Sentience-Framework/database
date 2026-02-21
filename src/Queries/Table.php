<?php

namespace Sentience\Database\Queries;

use Throwable;
use Sentience\Database\Databases\DatabaseInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Objects\ConditionGroup;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Results\Result;
use Sentience\Database\Results\ResultInterface;

class Table
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

    public function truncate(): void
    {
        $this->delete()->execute();
    }

    public function drop(): DropTableQuery
    {
        return $this->database->dropTable($this->table);
    }

    public function dropIfExists(): DropTableQuery
    {
        return $this->drop()->ifExists();
    }

    public function columns(): array
    {
        return array_keys(
            $this->select()
                ->limit(0)
                ->execute()
                ->columns()
        );
    }

    public function isEmpty(): bool
    {
        return $this->select()->limit(1)->count() > 0;
    }

    public function copyFrom(string|array|Raw|self $from, ?callable $map = null, bool $ignoreExceptions = false, bool $emulatePrepare = false): int
    {
        $table = $from instanceof self ? $from : $this->database->table($from);

        $columns = $table->columns();

        $result = $table->select()->execute($emulatePrepare);

        $count = 0;

        while ($assoc = $result->fetchAssoc()) {
            try {
                $this->insert(
                    array_filter(
                        $map ? $map($assoc) : $assoc,
                        fn (string $column) => in_array($column, $columns),
                        ARRAY_FILTER_USE_KEY
                    )
                )->execute($emulatePrepare);

                $count++;
            } catch (Throwable $exception) {
                if ($ignoreExceptions) {
                    continue;
                }

                throw $exception;
            }
        }

        return $count;
    }

    public function copyTo(string|array|Raw|self $to, ?callable $map = null, bool $ignoreExceptions = false, bool $emulatePrepare = false): int
    {
        $table = $to instanceof self ? $to : $this->database->table($to);

        $columns = $table->columns();

        $result = $this->select()->execute();

        $count = 0;

        while ($assoc = $result->fetchAssoc()) {
            try {
                $table->insert(
                    array_filter(
                        $map ? $map($assoc) : $assoc,
                        fn (string $column) => in_array($column, $columns),
                        ARRAY_FILTER_USE_KEY
                    )
                )->execute($emulatePrepare);

                $count++;
            } catch (Throwable $exception) {
                if ($ignoreExceptions) {
                    continue;
                }

                throw $exception;
            }
        }

        return $count;
    }
}
