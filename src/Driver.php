<?php

namespace Sentience\Database;

use Sentience\Database\Adapters\MySQLiAdapter;
use Sentience\Database\Adapters\PDOAdapter;
use Sentience\Database\Adapters\SQLite3Adapter;
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

    public function getAdapter(): string
    {
        return match ($this) {
            static::MARIADB,
            static::MYSQL => MySQLiAdapter::class,
            static::SQLITE => SQLite3Adapter::class,
            default => PDOAdapter::class
        };
    }

    public function getDialect(): string
    {
        return match ($this) {
            static::FIREBIRD => FirebirdDialect::class,
            static::MARIADB,
            static::MYSQL => MySQLDialect::class,
            static::PGSQL => PgSQLDialect::class,
            static::SQLITE => SQLiteDialect::class,
            default => SQLDialect::class
        };
    }
}
