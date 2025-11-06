<?php

namespace Sentience\Database\Adapters;

use Closure;
use Throwable;
use Sentience\Database\Driver;
use Sentience\Database\Queries\Query;

abstract class AdapterAbstract implements AdapterInterface
{
    public const string OPTIONS_PERSISTENT = 'persistent';
    public const string OPTIONS_PDO_DSN = 'dsn';
    public const string OPTIONS_FIREBIRD_EMBEDDED = 'embedded';
    public const string OPTIONS_MYSQL_CHARSET = 'charset';
    public const string OPTIONS_MYSQL_COLLATION = 'collation';
    public const string OPTIONS_MYSQL_ENGINE = 'engine';
    public const string OPTIONS_PGSQL_CLIENT_ENCODING = 'client_encoding';
    public const string OPTIONS_PGSQL_SEARCH_PATH = 'search_path';
    public const string OPTIONS_SQLITE_READ_ONLY = 'read_only';
    public const string OPTIONS_SQLITE_ENCRYPTION_KEY = 'encryption_key';
    public const string OPTIONS_SQLITE_BUSY_TIMEOUT = 'busy_timeout';
    public const string OPTIONS_SQLITE_ENCODING = 'encoding';
    public const string OPTIONS_SQLITE_JOURNAL_MODE = 'journal_mode';
    public const string OPTIONS_SQLITE_FOREIGN_KEYS = 'foreign_keys';
    public const string OPTIONS_SQLITE_OPTIMIZE = 'optimize';
    public const string REGEXP_LIKE_FUNCTION = 'REGEXP_LIKE';

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

    protected function debug(string $query, float $start, null|string|Throwable $error = null): void
    {
        if (!$this->debug) {
            return;
        }

        ($this->debug)($query, $start, $error instanceof Throwable ? $error->getMessage() : $error);
    }

    public function __destruct()
    {
        if (!$this->connected()) {
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
