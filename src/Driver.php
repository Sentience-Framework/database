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
use Sentience\Database\Dialects\OCIDialect;
use Sentience\Database\Dialects\PgSQLDialect;
use Sentience\Database\Dialects\SQLDialect;
use Sentience\Database\Dialects\SQLiteDialect;
use Sentience\Database\Dialects\SQLServerDialect;
use Sentience\Database\Sockets\SocketInterface;

enum Driver: string
{
    case FIREBIRD = 'firebird';
    case MARIADB = 'mariadb';
    case MYSQL = 'mysql';
    case OCI = 'oci';
    case PGSQL = 'pgsql';
    case SQLITE = 'sqlite';
    case SQLSRV = 'sqlsrv';

    case CUBRID = 'cubrid';
    case DB2 = 'ibm';
    case DBLIB = 'dblib';
    case INFORMIX = 'informix';
    case ODBC = 'odbc';

    public function getAdapter(
        string $name,
        ?SocketInterface $socket,
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

        return $adapter::fromSocket(
            $this,
            $name,
            $socket,
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
            static::OCI => new OCIDialect($this, $version),
            static::PGSQL => new PgSQLDialect($this, $version),
            static::SQLITE => new SQLiteDialect($this, $version),
            static::SQLSRV => new SQLServerDialect($this, $version),
            default => new SQLDialect($this, $version)
        };
    }
}
