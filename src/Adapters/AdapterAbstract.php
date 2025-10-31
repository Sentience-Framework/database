<?php

namespace Sentience\Database\Adapters;

use Closure;
use Throwable;
use Sentience\Database\Driver;
use Sentience\Database\Queries\Query;
use Sentience\Database\Sockets\SocketInterface;

abstract class AdapterAbstract implements AdapterInterface
{
    public const string REGEXP_LIKE_FUNCTION = 'REGEXP_LIKE';

    public function __construct(
        protected Driver $driver,
        protected string $name,
        protected ?SocketInterface $socket,
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

        $query .= ';';

        $this->exec($query);
    }

    protected function mysqlEngine(string $engine): void
    {
        $this->exec(
            sprintf(
                'SET SESSION default_storage_engine = %s;',
                $engine
            )
        );
    }

    protected function sqliteEncoding(string $encoding): void
    {
        $this->exec(
            sprintf(
                "PRAGMA encoding = '%s';",
                $encoding
            )
        );
    }

    protected function sqliteJournalMode(string $journalMode): void
    {
        $this->exec(
            sprintf(
                'PRAGMA journal_mode = %s;',
                $journalMode
            )
        );
    }

    protected function sqliteForeignKeys(bool $foreignKeys): void
    {
        if (!$foreignKeys) {
            return;
        }

        $this->exec('PRAGMA foreign_keys = ON;');
    }

    protected function sqliteOptimize(bool $optimize): void
    {
        if (!$optimize) {
            return;
        }

        $this->exec('PRAGMA optimize;');
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

    protected function debug(string|callable $query, float $start, null|string|Throwable $error = null): void
    {
        if (!$this->debug) {
            return;
        }

        ($this->debug)(is_callable($query) ? $query() : $query, $start, $error instanceof Throwable ? $error->getMessage() : $error);
    }
}
