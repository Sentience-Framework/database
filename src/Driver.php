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
use Sentience\Database\Sockets\SocketAbstract;

enum Driver: string implements DriverInterface
{
    case MariaDB = 'mariadb';
    case MySQL = 'mysql';
    case PgSQL = 'pgsql';
    case SQLite = 'sqlite';

    public function driver(): string
    {
        return $this->value;
    }

    public function adapter(
        string $name,
        ?SocketAbstract $socket,
        array $queries,
        array $options,
        ?Closure $debug,
        bool $usePDOAdapter = false
    ): AdapterInterface {
        $adapter = !$usePDOAdapter
            ? match ($this) {
                static::MariaDB,
                static::MySQL => MySQLiAdapter::class,
                static::SQLite => SQLite3Adapter::class,
                default => PDOAdapter::class
            }
        : PDOAdapter::class;

        return new $adapter(
            $this,
            $name,
            $socket,
            $queries,
            $options,
            $debug
        );
    }

    public function dialect(int|string $version, array $options = []): DialectInterface
    {
        return match ($this) {
            static::MariaDB,
            static::MySQL => new MySQLDialect($this, $version, $options),
            static::PgSQL => new PgSQLDialect($this, $version, $options),
            static::SQLite => new SQLiteDialect($this, $version, $options),
            default => new SQLDialect($this, $version, $options)
        };
    }
}
