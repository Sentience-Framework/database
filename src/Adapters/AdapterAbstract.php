<?php

namespace Sentience\Database\Adapters;

use Closure;
use Throwable;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Driver;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Query;

abstract class AdapterAbstract implements AdapterInterface
{
    protected bool $inTransaction = false;
    protected null|int|string $lastInsertId = null;

    public function __construct(
        protected Closure $connect,
        protected Driver $driver,
        protected array $queries,
        protected array $options,
        protected ?Closure $debug,
        protected bool $lazy = false
    ) {
        if (!$lazy) {
            $this->connect();
        }
    }

    public function disconnect(): void
    {
        $this->lastInsertId = null;
    }

    public function reconnect(): void
    {
        $this->disconnect();
        $this->connect();
    }

    public function enableLazy(bool $disconnect = true): void
    {
        $this->lazy = true;

        if ($disconnect) {
            $this->disconnect();
        }
    }

    public function disableLazy(bool $connect = true): void
    {
        $this->lazy = false;

        if ($connect) {
            $this->connect();
        }
    }

    public function isLazy(): bool
    {
        return $this->lazy;
    }

    protected function mysqlNames(Closure $execute, string $charset, ?string $collation): void
    {
        $query = sprintf(
            'SET NAMES %s',
            $charset
        );

        if ($collation) {
            $query .= sprintf(
                ' COLLATE %s',
                $collation
            );
        }

        $query .= ';';

        $execute($query);
    }

    protected function mysqlEngine(Closure $execute, string $engine): void
    {
        $execute(
            sprintf(
                'SET SESSION default_storage_engine = %s;',
                $engine
            )
        );
    }

    protected function sqliteEncoding(Closure $execute, string $encoding): void
    {
        $execute(
            sprintf(
                "PRAGMA encoding = '%s';",
                $encoding
            )
        );
    }

    protected function sqliteJournalMode(Closure $execute, string $journalMode): void
    {
        $execute(
            sprintf(
                'PRAGMA journal_mode = %s;',
                $journalMode
            )
        );
    }

    protected function sqliteForeignKeys(Closure $execute, bool $foreignKeys): void
    {
        if (!$foreignKeys) {
            return;
        }

        $execute('PRAGMA foreign_keys = ON;');
    }

    protected function sqliteOptimize(Closure $execute, bool $optimize): void
    {
        if (!$optimize) {
            return;
        }

        $execute('PRAGMA optimize;');
    }

    protected function regexpLikeFunction(string $value, string $pattern, string $flags): bool
    {
        return preg_match(
            sprintf(
                '/%s/%s',
                Query::escapeBackslash($pattern, ['/']),
                $flags
            ),
            $value
        );
    }

    protected function isInsertQuery(string|QueryWithParams $query): bool
    {
        return str_starts_with(
            strtoupper($query instanceof QueryWithParams ? $query->query : $query),
            'INSERT'
        );
    }

    public function beginTransaction(DialectInterface $dialect, ?string $name = null): void
    {
        $this->connect();

        if ($this->inTransaction()) {
            return;
        }

        $this->exec($dialect->beginTransaction($name)->toSql($dialect));

        $this->inTransaction = true;
    }

    public function commitTransaction(DialectInterface $dialect, ?string $name = null): void
    {
        if (!$this->isConnected()) {
            return;
        }

        if (!$this->inTransaction()) {
            return;
        }

        try {
            $this->exec($dialect->commitTransaction($name)->toSql($dialect));
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            $this->inTransaction = false;

            if ($this->lazy) {
                $this->disconnect();
            }
        }
    }

    public function rollbackTransaction(DialectInterface $dialect, ?string $name = null): void
    {
        if (!$this->isConnected()) {
            return;
        }

        if (!$this->inTransaction()) {
            return;
        }

        try {
            $this->exec($dialect->rollbackTransaction($name)->toSql($dialect));
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            $this->inTransaction = false;

            if ($this->lazy) {
                $this->disconnect();
            }
        }
    }

    public function beginSavepoint(DialectInterface $dialect, string $name): void
    {
        if (!$this->isConnected()) {
            return;
        }

        if (!$this->inTransaction()) {
            return;
        }

        if (!$dialect->savepoints()) {
            return;
        }

        $this->exec($dialect->beginSavepoint($name)->toSql($dialect));
    }

    public function commitSavepoint(DialectInterface $dialect, string $name): void
    {
        if (!$this->isConnected()) {
            return;
        }

        if (!$this->inTransaction()) {
            return;
        }

        if (!$dialect->savepoints()) {
            return;
        }

        $this->exec($dialect->commitSavepoint($name)->toSql($dialect));
    }

    public function rollbackSavepoint(DialectInterface $dialect, string $name): void
    {
        if (!$this->isConnected()) {
            return;
        }

        if (!$this->inTransaction()) {
            return;
        }

        if (!$dialect->savepoints()) {
            return;
        }

        $this->exec($dialect->rollbackSavepoint($name)->toSql($dialect));
    }

    public function inTransaction(): bool
    {
        if (!$this->isConnected()) {
            return false;
        }

        return $this->inTransaction;
    }

    protected function debug(string|callable $query, float $start, null|string|Throwable $error = null): void
    {
        if (!$this->debug) {
            return;
        }

        ($this->debug)(
            is_callable($query) ? $query() : $query,
            $start,
            $error instanceof Throwable ? $error->getMessage() : $error
        );
    }

    public function __destruct()
    {
        if (!$this->isConnected()) {
            return;
        }

        if ($this->driver == Driver::SQLITE) {
            $this->disconnect();
        }

        if (!($this->options[static::OPTIONS_PERSISTENT] ?? false)) {
            $this->disconnect();
        }
    }
}
