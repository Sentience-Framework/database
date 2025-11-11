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

    public function reconnect(): void
    {
        $this->disconnect();
        $this->connect();
    }

    public function ping(DialectInterface $dialect, bool $reconnect = false): bool
    {
        if (!$this->isConnected()) {
            return false;
        }

        $isConnected = false;

        try {
            $this->exec($dialect, 'SELECT 1');

            return true;
        } catch (Throwable $exception) {
        }

        if ($isConnected) {
            return true;
        }

        if (!$reconnect) {
            return false;
        }

        $this->reconnect();

        return true;
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

    protected function debug(DialectInterface $dialect, string|QueryWithParams $query, float $start, null|string|Throwable $error = null): void
    {
        if (!$this->debug) {
            return;
        }

        ($this->debug)(
            $query instanceof QueryWithParams ? $query->toSql($dialect) : $query,
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
