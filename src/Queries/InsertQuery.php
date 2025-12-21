<?php

namespace Sentience\Database\Queries;

use Closure;
use Throwable;
use Sentience\Database\Adapters\SQLite3Adapter;
use Sentience\Database\Driver;
use Sentience\Database\Exceptions\QueryException;
use Sentience\Database\Queries\Objects\ConditionGroup;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Traits\LastInsertIdTrait;
use Sentience\Database\Queries\Traits\OnConflictTrait;
use Sentience\Database\Queries\Traits\ReturningTrait;
use Sentience\Database\Queries\Traits\ValuesTrait;
use Sentience\Database\Results\Result;
use Sentience\Database\Results\ResultInterface;

class InsertQuery extends Query
{
    use LastInsertIdTrait;
    use OnConflictTrait;
    use ReturningTrait;
    use ValuesTrait;

    protected bool $emulateOnConflict = false;
    protected bool $emulateOnConflictInTransaction = false;
    protected bool $emulateReturning = false;

    public function toQueryWithParams(): QueryWithParams
    {
        return $this->dialect->insert(
            $this->table,
            $this->values,
            !$this->emulateOnConflict ? $this->onConflict : null,
            !$this->emulateReturning ? $this->returning : null,
            $this->lastInsertId
        );
    }

    public function toSql(): string
    {
        return parent::toSql();
    }

    public function execute(bool $emulatePrepare = false): ResultInterface
    {
        try {
            $previousEmulateReturning = $this->emulateReturning;

            /**
             * PHP's SQLite3 class doesn't handle returning well, because it re-executes the insert when fetching returning results.
             * This means that only when you have an ON CONFLICT specified, will returning work as intended when using SQLite3Adapter.
             *
             * Returning also doesn't work in a transaction,
             * because when a returning result is still unread the transaction cannot be committed.
             */
            if (
                $this->lastInsertId
                && (
                    $this->database->adapter() instanceof SQLite3Adapter
                    || $this->database->adapter()->driver() == Driver::SQLITE && $this->emulateOnConflictInTransaction
                )
            ) {
                $this->emulateReturning = true;
            }

            if (!$this->onConflict || !$this->emulateOnConflict && $this->dialect->onConflict()) {
                return $this->insert($emulatePrepare);
            }

            return $this->emulateOnConflictInTransaction
                ? $this->database->transactionInCallback(fn (): ResultInterface => $this->upsert($emulatePrepare))
                : $this->upsert($emulatePrepare);
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            $this->emulateReturning = $previousEmulateReturning;
        }
    }

    protected function upsert(bool $emulatePrepare): ResultInterface
    {
        if (is_string($this->onConflict->conflict)) {
            throw new QueryException('database does not support named constraints');
        }

        $conflict = [];

        foreach ($this->onConflict->conflict as $column) {
            if (!array_key_exists($column, $this->values)) {
                throw new QueryException('insert values does not contain constraint columns');
            }

            $value = $this->values[$column];

            $conflict[$column] = $value;
        }

        $result = $this->select(
            function (ConditionGroup $conditionGroup) use ($conflict): void {
                foreach ($conflict as $column => $value) {
                    $conditionGroup->whereEquals($column, $value);
                }
            },
            2,
            $emulatePrepare
        );

        $rows = $result->fetchAssocs();

        $count = count($rows);

        if ($count == 0) {
            return $this->insert($emulatePrepare);
        }

        if ($count > 1) {
            throw new QueryException('multiple rows in constraint');
        }

        return !is_null($this->onConflict->updates)
            ? $this->update($conflict, $emulatePrepare)
            : $this->ignore($result, $rows);
    }

    protected function select(Closure $whereGroup, int $limit, bool $emulatePrepare): ResultInterface
    {
        return $this->database->select($this->table)
            ->columns(
                !empty($this->returning)
                ? array_unique(array_filter([$this->lastInsertId, ...$this->returning]))
                : []
            )
            ->whereGroup($whereGroup)
            ->limit($limit)
            ->execute($emulatePrepare);
    }

    protected function insert(bool $emulatePrepare): ResultInterface
    {
        $result = parent::execute($emulatePrepare);

        if (!$this->lastInsertId || is_null($this->returning) || !$this->emulateReturning && $this->dialect->returning()) {
            return $result;
        }

        $lastInsertId = $this->database->lastInsertId();

        return $this->select(
            function (ConditionGroup $conditionGroup) use ($lastInsertId): ConditionGroup {
                if (empty($lastInsertId)) {
                    return $conditionGroup;
                }

                return $conditionGroup->whereEquals(
                    $this->lastInsertId,
                    $lastInsertId
                );
            },
            1,
            $emulatePrepare
        );
    }

    protected function update(array $conflict, bool $emulatePrepare): ResultInterface
    {
        $updateQuery = $this->database->update($this->table);

        $updates = !is_null($this->onConflict->updates)
            ? count($this->onConflict->updates) > 0 ? $this->onConflict->updates : $this->values
            : $conflict;

        $updateQuery->values($updates);

        foreach ($conflict as $column => $value) {
            $updateQuery->whereEquals($column, $value);
        }

        if (!is_null($this->returning)) {
            $updateQuery->returning($this->returning);
        }

        $result = $updateQuery->execute($emulatePrepare);

        if (is_null($this->returning) || !$this->emulateReturning && $this->dialect->returning()) {
            return $result;
        }

        return $this->select(
            function (ConditionGroup $conditionGroup) use ($conflict): void {
                foreach ($conflict as $column => $value) {
                    $conditionGroup->whereEquals($column, $value);
                }
            },
            1,
            $emulatePrepare
        );
    }

    protected function ignore(ResultInterface $result, array $rows): ResultInterface
    {
        $returning = !is_null($this->returning);

        return new Result(
            $returning ? $result->columns() : [],
            $returning ? $rows : []
        );
    }

    public function emulateOnConflict(string $lastInsertId, bool $inTransaction = false): static
    {
        $this->emulateOnConflict = true;
        $this->emulateOnConflictInTransaction = $inTransaction;

        $this->lastInsertId = $lastInsertId;

        return $this;
    }

    public function emulateReturning(string $lastInsertId): static
    {
        $this->emulateReturning = true;

        $this->lastInsertId = $lastInsertId;

        return $this;
    }
}
