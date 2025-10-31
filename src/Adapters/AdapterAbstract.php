<?php

namespace Sentience\Database\Adapters;

use Closure;
use Throwable;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Driver;
use Sentience\Database\Queries\Query;
use Sentience\Database\Sockets\SocketAbstract;

abstract class AdapterAbstract implements AdapterInterface
{
    public const string REGEXP_FUNCTION = 'REGEXP';
    public const string REGEXP_LIKE_FUNCTION = 'regexp_like';

    protected bool $inTransaction = false;

    public function __construct(
        protected Driver $driver,
        protected string $name,
        protected ?SocketAbstract $socket,
        protected array $queries,
        protected array $options,
        protected ?Closure $debug
    ) {
    }

    protected function mysqlNames(string $charset, ?string $collation): void
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

        $this->exec($query);
    }

    protected function mysqlEngine(string $engine): void
    {
        $this->exec(
            sprintf(
                'SET SESSION default_storage_engine = %s',
                $engine
            )
        );
    }

    protected function sqliteEncoding(string $encoding): void
    {
        $this->exec(
            sprintf(
                "PRAGMA encoding = '%s'",
                $encoding
            )
        );
    }

    protected function sqliteJournalMode(string $journalMode): void
    {
        $this->exec(
            sprintf(
                'PRAGMA journal_mode = %s',
                $journalMode
            )
        );
    }

    protected function sqliteForeignKeys(bool $foreignKeys): void
    {
        if (!$foreignKeys) {
            return;
        }

        $this->exec('PRAGMA foreign_keys = ON');
    }

    protected function sqliteCaseSensitiveLike(bool $caseSensitiveLike): void
    {
        if (!$caseSensitiveLike) {
            return;
        }

        $this->exec('PRAGMA case_sensitive_like = ON');
    }

    protected function sqliteOptimize(bool $optimize): void
    {
        if (!$optimize) {
            return;
        }

        $this->exec('PRAGMA optimize');
    }

    protected function regexpFunction(string $value, string $pattern): bool
    {
        return preg_match(
            sprintf(
                '/%s/',
                Query::escapeBackslash($pattern, ['/'])
            ),
            $value
        );
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

    public function beginTransaction(DialectInterface $dialect, ?string $name = null): void
    {
        if ($this->inTransaction()) {
            return;
        }

        $this->exec($dialect->beginTransaction($name)->toSql($dialect));

        $this->inTransaction = true;
    }

    public function commitTransaction(DialectInterface $dialect, ?string $name = null): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        try {
            $this->exec($dialect->commitTransaction($name)->toSql($dialect));
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            $this->inTransaction = false;
        }
    }

    public function rollbackTransaction(DialectInterface $dialect, ?string $name = null): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        try {
            $this->exec($dialect->rollbackTransaction($name)->toSql($dialect));
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            $this->inTransaction = false;
        }
    }

    public function beginSavepoint(DialectInterface $dialect, string $name): void
    {
        if (!$dialect->savepoints()) {
            return;
        }

        if (!$this->inTransaction()) {
            return;
        }

        $this->exec($dialect->beginSavepoint($name)->toSql($dialect));
    }

    public function commitSavepoint(DialectInterface $dialect, string $name): void
    {
        if (!$dialect->savepoints()) {
            return;
        }

        if (!$this->inTransaction()) {
            return;
        }

        $this->exec($dialect->commitSavepoint($name)->toSql($dialect));
    }

    public function rollbackSavepoint(DialectInterface $dialect, string $name): void
    {
        if (!$dialect->savepoints()) {
            return;
        }

        if (!$this->inTransaction()) {
            return;
        }

        $this->exec($dialect->rollbackSavepoint($name)->toSql($dialect));
    }

    public function inTransaction(): bool
    {
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
}
