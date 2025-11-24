<?php

namespace Sentience\Database\Queries;

use Closure;
use Sentience\Database\Exceptions\QueryException;
use Sentience\Database\Queries\Objects\ConditionGroup;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Traits\LastInsertIdTrait;
use Sentience\Database\Queries\Traits\OnConflictTrait;
use Sentience\Database\Queries\Traits\ReturningTrait;
use Sentience\Database\Queries\Traits\ValuesTrait;
use Sentience\Database\Results\ResultInterface;

class InsertQuery extends Query
{
    use LastInsertIdTrait;
    use OnConflictTrait;
    use ReturningTrait;
    use ValuesTrait;

    public function toQueryWithParams(): QueryWithParams
    {
        return $this->dialect->insert(
            $this->table,
            $this->values,
            $this->onConflict,
            $this->returning,
            $this->lastInsertId
        );
    }

    public function toSql(): string
    {
        return parent::toSql();
    }

    public function execute(bool $emulatePrepare = false): ResultInterface
    {
        if (!$this->onConflict || $this->dialect->onConflict()) {
            return $this->insert($emulatePrepare);
        }

        if (is_string($this->onConflict->conflict)) {
            throw new QueryException('database does not support named constraints');
        }

        $conflict = [];

        foreach ($this->onConflict->conflict as $column) {
            if (!array_key_exists($column, $this->values)) {
                continue;
            }

            $value = $this->values[$column];

            $conflict[$column] = $value;
        }

        $count = $this->count($conflict, $emulatePrepare);

        if ($count == 0) {
            return $this->insert($emulatePrepare);
        }

        if ($count > 1) {
            throw new QueryException('multiple rows in constraint');
        }

        return $this->update($conflict, $emulatePrepare);
    }

    protected function count(array $conflict, bool $emulatePrepare): int
    {
        $selectQuery = $this->database->select($this->table);

        foreach ($conflict as $column => $value) {
            $selectQuery->whereEquals($column, $value);
        }

        $selectQuery->limit(2);

        return $selectQuery->count(null, $emulatePrepare);
    }

    protected function select(Closure $whereGroup, bool $emulatePrepare): ResultInterface
    {
        return $this->database->select($this->table)
            ->columns(
                count($this->returning) > 0
                ? array_unique([$this->lastInsertId, ...$this->returning])
                : []
            )
            ->whereGroup($whereGroup)
            ->limit(1)
            ->execute($emulatePrepare);
    }

    protected function insert(bool $emulatePrepare): ResultInterface
    {
        $result = parent::execute($emulatePrepare);

        if (!$this->lastInsertId || is_null($this->returning) || $this->dialect->returning()) {
            return $result;
        }

        $lastInsertId = $this->database->lastInsertId();

        if (empty($lastInsertId)) {
            return $result;
        }

        return $this->select(
            fn (ConditionGroup $conditionGroup): ConditionGroup => $conditionGroup->whereEquals(
                $this->lastInsertId,
                $lastInsertId
            ),
            $emulatePrepare
        );
    }

    protected function update(array $conflict, bool $emulatePrepare): ResultInterface
    {
        if (!is_null($this->onConflict->updates)) {
            $updateQuery = $this->database->update($this->table);

            $updates = count($this->onConflict->updates) > 0 ? $this->onConflict->updates : $this->values;

            $updateQuery->values($updates);

            foreach ($conflict as $column => $value) {
                $updateQuery->whereEquals($column, $value);
            }

            if (!is_null($this->returning)) {
                $updateQuery->returning($this->returning);
            }

            $result = $updateQuery->execute($emulatePrepare);

            if (is_null($this->returning) || $this->dialect->returning()) {
                return $result;
            }
        }

        return $this->select(
            function (ConditionGroup $conditionGroup) use ($conflict): void {
                foreach ($conflict as $column => $value) {
                    $conditionGroup->whereEquals($column, $value);
                }
            },
            $emulatePrepare
        );
    }
}
