<?php

namespace Sentience\Database\Adapters;

use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Results\ResultInterface;

interface AdapterInterface
{
    public const string OPTIONS_VERSION = 'version';
    public const string OPTIONS_PERSISTENT = 'persistent';
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

    public function connect(): void;
    public function disconnect(): void;
    public function reconnect(): void;
    public function isConnected(): bool;
    public function ping(DialectInterface $dialect, bool $reconnect = false): bool;
    public function enableLazy(bool $disconnect = true): void;
    public function disableLazy(bool $connect = true): void;
    public function isLazy(): bool;
    public function version(): int|string;
    public function exec(DialectInterface $dialect, string $query): void;
    public function query(DialectInterface $dialect, string $query): ResultInterface;
    public function queryWithParams(DialectInterface $dialect, QueryWithParams $queryWithParams, bool $emulatePrepare): ResultInterface;
    public function beginTransaction(DialectInterface $dialect): void;
    public function commitTransaction(DialectInterface $dialect): void;
    public function rollbackTransaction(DialectInterface $dialect): void;
    public function inTransaction(): bool;
    public function lastInsertId(?string $name = null): null|int|string;
}
