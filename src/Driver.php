<?php

namespace Sentience\Database;

use Closure;
use mysqli;
use PDO;
use SQLite3;
use Sentience\Database\Adapters\AdapterAbstract;
use Sentience\Database\Adapters\AdapterInterface;
use Sentience\Database\Adapters\MySQLiAdapter;
use Sentience\Database\Adapters\PDOAdapter;
use Sentience\Database\Adapters\SQLite3Adapter;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Dialects\FirebirdDialect;
use Sentience\Database\Dialects\MySQLDialect;
use Sentience\Database\Dialects\PgSQLDialect;
use Sentience\Database\Dialects\SQLDialect;
use Sentience\Database\Dialects\SQLiteDialect;
use Sentience\Database\Exceptions\DriverException;

enum Driver: string
{
    case FIREBIRD = 'firebird';
    case MARIADB = 'mariadb';
    case MYSQL = 'mysql';
    case PGSQL = 'pgsql';
    case SQLITE = 'sqlite';

    case CUBRID = 'cubrid';
    case DB2 = 'ibm';
    case DBLIB = 'dblib';
    case INFORMIX = 'informix';
    case ODBC = 'odbc';
    case ORACLE = 'oci';
    case SQLSRV = 'sqlsrv';

    public function getAdapter(
        string $host,
        int $port,
        string $name,
        string $username,
        string $password,
        array $queries,
        array $options,
        ?Closure $debug,
        bool $usePdoAdapter = false,
        bool $lazy = false
    ): AdapterInterface {
        $adapter = !$usePdoAdapter
            ? match ($this) {
                static::MARIADB,
                static::MYSQL => MySQLiAdapter::class,
                static::SQLITE => SQLite3Adapter::class,
                default => PDOAdapter::class
            }
        : PDOAdapter::class;

        return $this->connect(
            $adapter,
            $host,
            $port,
            $name,
            $username,
            $password,
            $queries,
            $options,
            $debug,
            $lazy
        );
    }

    public function getDialect(int|string $version): DialectInterface
    {
        return match ($this) {
            static::FIREBIRD => new FirebirdDialect($this, $version),
            static::MARIADB,
            static::MYSQL => new MySQLDialect($this, $version),
            static::PGSQL => new PgSQLDialect($this, $version),
            static::SQLITE => new SQLiteDialect($this, $version),
            default => new SQLDialect($this, $version)
        };
    }

    public function isSupportedBySentience(): bool
    {
        return match ($this) {
            static::FIREBIRD,
            static::MARIADB,
            static::MYSQL,
            static::PGSQL,
            static::SQLITE => true,
            default => false
        };
    }

    protected function connect(
        string $adapter,
        string $host,
        int $port,
        string $name,
        string $username,
        string $password,
        array $queries,
        array $options,
        ?Closure $debug,
        bool $lazy = false
    ): AdapterInterface {
        if ($adapter == MySQLiAdapter::class) {
            return new MySQLiAdapter(
                fn (): mysqli => new mysqli(
                    ($options[AdapterAbstract::OPTIONS_PERSISTENT] ?? false)
                    ? sprintf('p:%s', $host)
                    : $host,
                    $username,
                    $password,
                    $name,
                    $port
                ),
                $this,
                $queries,
                $options,
                $debug,
                $lazy
            );
        }

        if ($adapter == SQLite3Adapter::class) {
            return new SQLite3Adapter(
                fn (): SQLite3 => new SQLite3(
                    $name,
                    !($options[AdapterAbstract::OPTIONS_SQLITE_READ_ONLY] ?? false)
                    ? SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE
                    : SQLITE3_OPEN_READONLY,
                    (string) ($options[AdapterAbstract::OPTIONS_SQLITE_ENCRYPTION_KEY] ?? '')
                ),
                $this,
                $queries,
                $options,
                $debug,
                $lazy
            );
        }

        return new PDOAdapter(
            function () use ($host, $port, $name, $username, $password, $options): PDO {
                $dsn = (function (string $host, int $port, string $name, array $options): string {
                    if (array_key_exists(AdapterAbstract::OPTIONS_PDO_DSN, $options)) {
                        return (string) $options[AdapterAbstract::OPTIONS_PDO_DSN];
                    }

                    if (!in_array($this, [Driver::MARIADB, Driver::MYSQL, Driver::PGSQL, Driver::SQLITE])) {
                        throw new DriverException('this driver requires a dsn');
                    }

                    if ($this == Driver::SQLITE) {
                        return sprintf(
                            '%s:%s',
                            $this->value,
                            $name
                        );
                    }

                    $dsn = sprintf(
                        '%s:host=%s;port=%s;dbname=%s',
                        $this == Driver::MARIADB ? Driver::MYSQL->value : $this->value,
                        $host,
                        $port,
                        $name
                    );

                    if ($this == Driver::PGSQL) {
                        if (array_key_exists(AdapterAbstract::OPTIONS_PGSQL_CLIENT_ENCODING, $options)) {
                            $dsn .= sprintf(
                                ";options='--client_encoding=%s'",
                                (string) $options[AdapterAbstract::OPTIONS_PGSQL_CLIENT_ENCODING]
                            );
                        }
                    }

                    return $dsn;
                })($host, $port, $name, $options);

                return new PDO(
                    $dsn,
                    $username,
                    $password
                );
            },
            $this,
            $queries,
            $options,
            $debug,
            $lazy
        );
    }
}
