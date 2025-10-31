<?php

namespace Sentience\Database\Adapters;

use Closure;
use Throwable;
use Sentience\Database\Driver;
use Sentience\Database\Queries\Query;

abstract class AdapterAbstract implements AdapterInterface
{
    public const string OPTIONS_PDO_DSN = 'dsn';
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
        protected Driver $driver,
        protected string $host,
        protected int $port,
        protected string $name,
        protected string $username,
        protected string $password,
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

    protected function debug(string $query, float $start, null|string|Throwable $error = null): void
    {
        if (!$this->debug) {
            return;
        }

        ($this->debug)($query, $start, $error instanceof Throwable ? $error->getMessage() : $error);
    }
}
