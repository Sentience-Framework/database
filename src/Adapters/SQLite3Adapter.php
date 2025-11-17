<?php

namespace Sentience\Database\Adapters;

use Closure;
use SQLite3;
use SQLite3Stmt;
use Throwable;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Driver;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Results\Result;
use Sentience\Database\Results\SQLite3Result;

class SQLite3Adapter extends AdapterAbstract
{
    protected ?SQLite3 $sqlite3 = null;

    public static function sqlite3(
        string $file,
        array $queries,
        array $options,
        ?Closure $debug,
        bool $lazy = false
    ): static {
        return new static(
            fn (): SQLite3 => new SQLite3(
                $file,
                !($options[AdapterInterface::OPTIONS_SQLITE_READ_ONLY] ?? false)
                ? SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE
                : SQLITE3_OPEN_READONLY,
                (string) ($options[AdapterInterface::OPTIONS_SQLITE_ENCRYPTION_KEY] ?? '')
            ),
            Driver::SQLITE,
            $queries,
            $options,
            $debug,
            $lazy
        );
    }

    public function connect(): void
    {
        if ($this->isConnected()) {
            return;
        }

        $this->sqlite3 = ($this->connect)();

        $this->sqlite3->enableExceptions(true);

        $this->sqlite3->createFunction(
            static::REGEXP_LIKE_FUNCTION,
            fn (string $value, string $pattern, string $flags = ''): bool => $this->regexpLikeFunction(
                $value,
                $pattern,
                $flags
            )
        );

        if (array_key_exists(static::OPTIONS_SQLITE_BUSY_TIMEOUT, $this->options)) {
            $this->sqlite3->busyTimeout((int) $this->options[static::OPTIONS_SQLITE_BUSY_TIMEOUT]);
        }

        if (array_key_exists(static::OPTIONS_SQLITE_ENCODING, $this->options)) {
            $this->sqliteEncoding(
                function (string $query): void {
                    $this->sqlite3->exec($query);
                },
                (string) $this->options[static::OPTIONS_SQLITE_ENCODING]
            );
        }

        if (array_key_exists(static::OPTIONS_SQLITE_JOURNAL_MODE, $this->options)) {
            $this->sqliteJournalMode(
                function (string $query): void {
                    $this->sqlite3->exec($query);
                },
                (string) $this->options[static::OPTIONS_SQLITE_JOURNAL_MODE]
            );
        }

        if (array_key_exists(static::OPTIONS_SQLITE_FOREIGN_KEYS, $this->options)) {
            $this->sqliteForeignKeys(
                function (string $query): void {
                    $this->sqlite3->exec($query);
                },
                (bool) $this->options[static::OPTIONS_SQLITE_FOREIGN_KEYS]
            );
        }

        foreach ($this->queries as $query) {
            $this->sqlite3->exec($query);
        }
    }

    public function disconnect(): void
    {
        if (!$this->isConnected()) {
            return;
        }

        $this->sqliteOptimize(
            function (string $query): void {
                $this->sqlite3->exec($query);
            },
            $this->options[static::OPTIONS_SQLITE_OPTIMIZE] ?? false
        );

        $this->sqlite3->close();

        $this->sqlite3 = null;

        parent::disconnect();
    }

    public function isConnected(): bool
    {
        return !is_null($this->sqlite3);
    }

    public function version(): string
    {
        return SQLite3::version()['versionString'];
    }

    public function exec(string $query): void
    {
        $this->connect();

        $start = microtime(true);

        try {
            $this->sqlite3->exec($query);

            if ($this->lazy && $this->isInsertQuery($query)) {
                $this->lastInsertId();
            }
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

            throw $exception;
        } finally {
            if ($this->lazy && !$this->inTransaction()) {
                $this->disconnect();
            }
        }

        $this->debug($query, $start);
    }

    public function query(string $query): SQLite3Result|Result
    {
        $this->connect();

        $start = microtime(true);

        try {
            $sqlite3Result = $this->sqlite3->query($query);

            $this->debug($query, $start);

            if ($this->lazy && $this->isInsertQuery($query)) {
                $this->lastInsertId();
            }

            $result = new SQLite3Result($sqlite3Result);

            return $this->lazy
                ? Result::fromInterface($result)
                : $result;
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

            throw $exception;
        } finally {
            if ($this->lazy && !$this->inTransaction()) {
                $this->disconnect();
            }
        }
    }

    public function queryWithParams(DialectInterface $dialect, QueryWithParams $queryWithParams, bool $emulatePrepare): SQLite3Result|Result
    {
        $this->connect();

        if ($emulatePrepare) {
            return $this->query($queryWithParams->toSql($dialect));
        }

        $start = microtime(true);

        try {
            $sqlite3Stmt = $this->sqlite3->prepare($queryWithParams->query);
        } catch (Throwable $exception) {
            if ($this->lazy && !$this->inTransaction()) {
                $this->disconnect();
            }

            $this->debug(
                $queryWithParams->toSql($dialect),
                $start,
                $exception
            );

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
            if ($this->lazy && !$this->inTransaction()) {
                $this->disconnect();
            }

            $this->debug(
                $queryWithParams->toSql($dialect),
                $start,
                $exception
            );

            throw $exception;
        }

        $this->debug($queryWithParams->toSql($dialect), $start);

        if ($this->lazy && $this->isInsertQuery($queryWithParams)) {
            $this->lastInsertId();
        }

        $result = new SQLite3Result($sqlite3Result);

        if ($this->lazy && !$this->inTransaction()) {
            $result = Result::fromInterface($result);

            $this->disconnect();
        }

        return $result;
    }

    public function lastInsertId(?string $name = null): ?int
    {
        if ($this->lazy && $this->lastInsertId) {
            return $this->lastInsertId;
        }

        if (!$this->isConnected()) {
            return null;
        }

        $lastInsertId = $this->sqlite3->lastInsertRowID();

        $this->lastInsertId = $this->lazy ? $lastInsertId : null;

        return $lastInsertId;
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
