<?php

namespace Sentience\Database\Adapters;

use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Results\ResultInterface;

interface AdapterInterface
{
    public const string CLASS_MYSQLI = 'mysqli';
    public const string CLASS_PDO = 'PDO';
    public const string CLASS_SQLITE3 = 'SQLite3';
    public const string OPTIONS_PERSISTENT = 'persistent';
    public const string OPTIONS_PDO_DSN = 'dsn';
    public const string OPTIONS_MYSQL_CHARSET = 'charset';
    public const string OPTIONS_MYSQL_COLLATION = 'collation';
    public const string OPTIONS_MYSQL_ENGINE = 'engine';
    public const string OPTIONS_PGSQL_SSL_MODE = 'ssl_mode';
    public const string OPTIONS_PGSQL_SSL_CERT = 'ssl_cert';
    public const string OPTIONS_PGSQL_SSL_KEY = 'ssl_key';
    public const string OPTIONS_PGSQL_SSL_ROOT_CERT = 'ssl_root_cert';
    public const string OPTIONS_PGSQL_SSL_CRL = 'ssl_crl';
    public const string OPTIONS_PGSQL_CLIENT_ENCODING = 'client_encoding';
    public const string OPTIONS_PGSQL_SEARCH_PATH = 'search_path';
    public const string OPTIONS_SQLITE_READ_ONLY = 'read_only';
    public const string OPTIONS_SQLITE_ENCRYPTION_KEY = 'encryption_key';
    public const string OPTIONS_SQLITE_BUSY_TIMEOUT = 'busy_timeout';
    public const string OPTIONS_SQLITE_ENCODING = 'encoding';
    public const string OPTIONS_SQLITE_JOURNAL_MODE = 'journal_mode';
    public const string OPTIONS_SQLITE_FOREIGN_KEYS = 'foreign_keys';
    public const string OPTIONS_SQLITE_OPTIMIZE = 'optimize';
    public const string OPTIONS_SQLSRV_ENCRYPT = 'encrypt';
    public const string OPTIONS_SQLSRV_TRUST_SERVER_CERTIFICATE = 'trust_server_certificate';

    public function version(): int|string;
    public function exec(string $query): void;
    public function query(string $query): ResultInterface;
    public function queryWithParams(DialectInterface $dialect, QueryWithParams $queryWithParams, bool $emulatePrepare): ResultInterface;
    public function beginTransaction(DialectInterface $dialect, ?string $name = null): void;
    public function commitTransaction(DialectInterface $dialect, ?string $name = null): void;
    public function rollbackTransaction(DialectInterface $dialect, ?string $name = null): void;
    public function beginSavepoint(DialectInterface $dialect, string $name): void;
    public function commitSavepoint(DialectInterface $dialect, string $name): void;
    public function rollbackSavepoint(DialectInterface $dialect, string $name): void;
    public function inTransaction(): bool;
    public function lastInsertId(?string $name = null): null|int|string;
}
