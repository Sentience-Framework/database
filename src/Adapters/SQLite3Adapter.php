<?php

namespace Sentience\Database\Adapters;

use Closure;
use SQLite3;
use SQLite3Stmt;
use Throwable;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Driver;
use Sentience\Database\Exceptions\AdapterException;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Results\SQLite3Result;
use Sentience\Database\Sockets\SocketAbstract;

class SQLite3Adapter extends AdapterAbstract
{
    protected SQLite3 $sqlite3;

    public function __construct(
        Driver $driver,
        string $name,
        ?SocketAbstract $socket,
        array $queries,
        array $options,
        ?Closure $debug
    ) {
        if (!class_exists(static::CLASS_SQLITE3)) {
            throw new AdapterException('SQLite3 extension is not installed');
        }

        parent::__construct(
            $driver,
            $name,
            $socket,
            $queries,
            $options,
            $debug
        );

        $this->sqlite3 = new SQLite3(
            $name,
            !($options[static::OPTIONS_SQLITE_READ_ONLY] ?? false)
            ? SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE
            : SQLITE3_OPEN_READONLY,
            (string) ($options[static::OPTIONS_SQLITE_ENCRYPTION_KEY] ?? '')
        );

        $this->sqlite3->enableExceptions(true);

        $this->sqlite3->createFunction(
            static::REGEXP_LIKE_FUNCTION,
            fn (string $value, string $pattern, string $flags = ''): bool => $this->regexpLikeFunction(
                $value,
                $pattern,
                $flags
            )
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
        if ($emulatePrepare) {
            return $this->query($queryWithParams->toSql($dialect));
        }

        $start = microtime(true);

        try {
            $sqlite3Stmt = $this->sqlite3->prepare($queryWithParams->query);
        } catch (Throwable $exception) {
            $this->debug(fn (): string => $queryWithParams->toSql($dialect), $start, $exception);

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

            ctype_digit((string) $key)
                ? $this->bindValue($sqlite3Stmt, $key, $value, $type)
                : $this->bindParam($sqlite3Stmt, $key, $value, $type);
        }

        try {
            $sqlite3Result = $sqlite3Stmt->execute();
        } catch (Throwable $exception) {
            $this->debug(fn (): string => $queryWithParams->toSql($dialect), $start, $exception);

            throw $exception;
        }

        $this->debug(fn (): string => $queryWithParams->toSql($dialect), $start);

        return new SQLite3Result($sqlite3Result);
    }

    public function beginTransaction(DialectInterface $dialect, ?string $name = null): void
    {
        parent::beginTransaction($dialect, null);
    }

    public function commitTransaction(DialectInterface $dialect, ?string $name = null): void
    {
        parent::commitTransaction($dialect, null);
    }

    public function rollbackTransaction(DialectInterface $dialect, ?string $name = null): void
    {
        parent::rollbackTransaction($dialect, null);
    }

    public function lastInsertId(?string $name = null): int
    {
        return $this->sqlite3->lastInsertRowID();
    }

    protected function bindValue(SQLite3Stmt $sqlite3Stmt, int $key, null|int|float|string $value, int $type): void
    {
        $sqlite3Stmt->bindValue($key + 1, $value, $type);
    }

    protected function bindParam(SQLite3Stmt $sqlite3Stmt, string $key, null|int|float|string $value, int $type): void
    {
        $sqlite3Stmt->bindParam($key, $value, $type);
    }

    public function __destruct()
    {
        $this->sqliteOptimize($this->options[static::OPTIONS_SQLITE_OPTIMIZE] ?? false);

        if (!($this->options[static::OPTIONS_PERSISTENT] ?? false)) {
            $this->sqlite3->close();
        }
    }
}
