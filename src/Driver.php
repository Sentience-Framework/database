<?php

namespace Sentience\Database;

use Closure;
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

        return match (true) {
            $adapter instanceof MySQLiAdapter => MySQLiAdapter::mysqli(
                $this,
                $host,
                $port,
                $name,
                $username,
                $password,
                $queries,
                $options,
                $debug,
                $lazy
            ),
            $adapter instanceof SQLite3Adapter => SQLite3Adapter::sqlite3(
                $name,
                $queries,
                $options,
                $debug,
                $lazy
            ),
            default => PDOAdapter::pdo(
                $this,
                $host,
                $port,
                $name,
                $username,
                $password,
                $queries,
                $options,
                $debug,
                $lazy
            )
        };
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
}
