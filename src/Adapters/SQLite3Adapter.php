<?php

namespace Sentience\Database\Adapters;

use Closure;
use SQLite3;
use SQLite3Stmt;
use Throwable;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Driver;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Results\SQLite3Result;

class SQLite3Adapter extends AdapterAbstract
{
    protected SQLite3 $sqlite3;
    protected bool $inTransaction = false;

    public function __construct(
        Driver $driver,
        string $host,
        int $port,
        string $name,
        string $username,
        string $password,
        array $queries,
        array $options,
        ?Closure $debug
    ) {
        parent::__construct(
            $driver,
            $host,
            $port,
            $name,
            $username,
            $password,
            $queries,
            $options,
            $debug
        );

        $this->sqlite3 = new SQLite3(
            $name,
            ($options[static::OPTIONS_SQLITE_READ_ONLY] ?? false)
            ? SQLITE3_OPEN_READONLY | SQLITE3_OPEN_CREATE
            : SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE,
            $options[static::OPTIONS_SQLITE_ENCRYPTION_KEY] ?? ''
        );

        $this->sqlite3->createFunction(
            static::REGEXP_FUNCTION,
            fn (string $pattern, string $value): bool => $this->regexpFunction($pattern, $value),
            static::REGEXP_FUNCTION_PARAMETER_COUNT
        );

        if (array_key_exists(static::OPTIONS_SQLITE_BUSY_TIMEOUT, $options)) {
            $this->sqlite3->busyTimeout((int) $options[static::OPTIONS_SQLITE_BUSY_TIMEOUT]);
        }

        if (array_key_exists(static::OPTIONS_SQLITE_ENCODING, $options)) {
            $this->sqliteEncoding((string) $options[static::OPTIONS_SQLITE_ENCODING]);
        }

        if (array_key_exists(static::OPTIONS_SQLITE_JOURNAL_MODE, $options)) {
            $this->sqliteJournalMode((string) $options[static::OPTIONS_SQLITE_JOURNAL_MODE]);
        }

        if (array_key_exists(static::OPTIONS_SQLITE_FOREIGN_KEYS, $options)) {
            $this->sqliteForeignKeys((bool) $options[static::OPTIONS_SQLITE_FOREIGN_KEYS]);
        }

        foreach ($queries as $query) {
            $this->query($query);
        }
    }

    public function query(string $query): void
    {
        $start = microtime(true);

        try {
            $this->sqlite3->exec($query);
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

            throw $exception;
        }

        $this->debug($query, $start);
    }

    public function queryWithParams(DialectInterface $dialect, QueryWithParams $queryWithParams): SQLite3Result
    {
        $query = $queryWithParams->toRawQuery($dialect);

        $start = microtime(true);

        try {
            $sqlite3Stmt = $this->sqlite3->prepare($queryWithParams->query);
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

            throw $exception;
        }

        foreach ($queryWithParams->params as $key => $param) {
            $value = $dialect->castToDriver($param);

            $type = match (get_debug_type($value)) {
                'null' => SQLITE3_NULL,
                'int' => SQLITE3_INTEGER,
                'float' => SQLITE3_FLOAT,
                'string' => SQLITE3_TEXT,
                default => SQLITE3_TEXT
            };

            is_numeric($key)
                ? $this->bindValue($sqlite3Stmt, $key, $value, $type)
                : $this->bindParam($sqlite3Stmt, $key, $value, $type);
        }

        try {
            $sqlite3Result = $sqlite3Stmt->execute();
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

            throw $exception;
        }

        $this->debug($query, $start);

        return new SQLite3Result($sqlite3Result);
    }

    public function beginTransaction(): void
    {
        if ($this->inTransaction()) {
            return;
        }

        $this->query('BEGIN;');

        $this->inTransaction = true;
    }

    public function commitTransaction(): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        $this->query('COMMIT;');

        $this->inTransaction = false;
    }

    public function rollbackTransaction(): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        $this->query('ROLLBACK;');

        $this->inTransaction = false;
    }

    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }

    public function lastInsertId(?string $name = null): string
    {
        return (string) $this->sqlite3->lastInsertRowID();
    }

    protected function bindValue(SQLite3Stmt $sqlite3Stmt, int $key, mixed $value, int $type): void
    {
        $sqlite3Stmt->bindValue($key + 1, $value, $type);
    }

    protected function bindParam(SQLite3Stmt $sqlite3Stmt, string $key, mixed $value, int $type): void
    {
        $sqlite3Stmt->bindParam($key, $value, $type);
    }
}
