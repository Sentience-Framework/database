<?php

namespace Sentience\Database;

use Closure;
use Sentience\Database\Adapters\AdapterInterface;
use Sentience\Database\Adapters\MySQLiAdapter;
use Sentience\Database\Adapters\PDOAdapter;
use Sentience\Database\Adapters\SQLite3Adapter;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Dialects\MySQLDialect;
use Sentience\Database\Dialects\PgSQLDialect;
use Sentience\Database\Dialects\SQLDialect;
use Sentience\Database\Dialects\SQLiteDialect;

enum Driver: string
{
    case MYSQL = 'mysql';
    case PGSQL = 'pgsql';
    case SQLITE = 'sqlite';

    public function getAdapter(
        string $host,
        int $port,
        string $name,
        string $username,
        string $password,
        array $queries,
        array $options,
        ?Closure $debug,
        bool $usePDOAdapter = false
    ): AdapterInterface {
        $adapter = !$usePDOAdapter
            ? match ($this) {
                static::MYSQL => MySQLiAdapter::class,
                static::PGSQL => PDOAdapter::class,
                static::SQLITE => SQLite3Adapter::class,
                default => PDOAdapter::class
            }
        : PDOAdapter::class;

        return new $adapter(
            $this,
            $host,
            $port,
            $name,
            $username,
            $password,
            $queries,
            $options,
            $debug
        );
    }

    public function getDialect(): DialectInterface
    {
        return match ($this) {
            static::MYSQL => new MySQLDialect(),
            static::PGSQL => new PgSQLDialect(),
            static::SQLITE => new SQLiteDialect(),
            default => new SQLDialect()
        };
    }
}
