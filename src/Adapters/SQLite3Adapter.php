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
    protected bool $inTransaction = false;

    public static function connect(
        Driver $driver,
        string $host,
        int $port,
        string $name,
        string $username,
        string $password,
        array $queries,
        array $options,
        ?Closure $debug
    ): static {
        $sqlite3 = new SQLite3(
            $name,
            !($options[static::OPTIONS_SQLITE_READ_ONLY] ?? false)
            ? SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE
            : SQLITE3_OPEN_READONLY,
            (string) ($options[static::OPTIONS_SQLITE_ENCRYPTION_KEY] ?? '')
        );

        $sqlite3->enableExceptions(true);

        return new static(
            $sqlite3,
            $driver,
            $queries,
            $options,
            $debug
        );
    }

    public function __construct(
        protected SQLite3 $sqlite3,
        Driver $driver,
        array $queries,
        array $options,
        ?Closure $debug
    ) {
        parent::__construct(
            $driver,
            $queries,
            $options,
            $debug
        );

        $this->sqlite3->createFunction(
            static::REGEXP_LIKE_FUNCTION,
            fn (string $value, string $pattern, string $flags = ''): bool => $this->regexpLikeFunction($value, $pattern, $flags)
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
            $this->exec($query);
        }
    }

    public function version(): string
    {
        return SQLite3::version()['versionString'];
    }

    public function exec(string $query): void
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

    public function query(string $query): SQLite3Result
    {
        $start = microtime(true);

        try {
            $sqlite3Result = $this->sqlite3->query($query);

            $this->debug($query, $start);

            return new SQLite3Result($sqlite3Result);
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

            throw $exception;
        }
    }

    public function queryWithParams(DialectInterface $dialect, QueryWithParams $queryWithParams, bool $emulatePrepare): SQLite3Result
    {
        $query = $queryWithParams->toSql($dialect);

        if ($emulatePrepare) {
            return $this->query($query);
        }

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

        $this->exec('BEGIN;');

        $this->inTransaction = true;
    }

    public function commitTransaction(): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        try {
            $this->exec('COMMIT;');
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            $this->inTransaction = false;
        }
    }

    public function rollbackTransaction(): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        try {
            $this->exec('ROLLBACK;');
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            $this->inTransaction = false;
        }
    }

    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }

    public function lastInsertId(?string $name = null): int
    {
        return $this->sqlite3->lastInsertRowID();
    }

    protected function bindValue(SQLite3Stmt $sqlite3Stmt, int $key, mixed $value, int $type): void
    {
        $sqlite3Stmt->bindValue($key + 1, $value, $type);
    }

    protected function bindParam(SQLite3Stmt $sqlite3Stmt, string $key, mixed $value, int $type): void
    {
        $sqlite3Stmt->bindParam($key, $value, $type);
    }

    public function __destruct()
    {
        $this->sqliteOptimize($this->options[static::OPTIONS_SQLITE_OPTIMIZE] ?? false);

        $this->sqlite3->close();
    }
}
