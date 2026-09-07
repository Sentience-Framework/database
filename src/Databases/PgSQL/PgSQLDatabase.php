<?php

namespace Sentience\Database\Databases\PgSQL;

use Closure;
use Sentience\Database\Databases\DatabaseAbstract;
use Sentience\Database\Driver;
use Sentience\Database\Sockets\NetworkSocket;
use Sentience\Database\Sockets\UnixSocket;

class PgSQLDatabase extends DatabaseAbstract
{
    public const Driver DRIVER = Driver::PgSQL;

    public static function network(
        string $name,
        string $username,
        ?string $password,
        string $host = 'localhost',
        int $port = 5432,
        array $queries = [],
        array $options = [],
        ?Closure $debug = null
    ): static {
        $driver = static::DRIVER;

        $adapter = $driver->adapter(
            $name,
            new NetworkSocket($host, $port, $username, $password),
            $queries,
            $options,
            $debug
        );

        $version = $adapter->version();

        $dialect = $driver->dialect($version, $options);

        $schema = $driver->schema();

        return new static($adapter, $dialect, $schema);
    }

    public static function unixSocket(
        string $name,
        string $username,
        ?string $password,
        string $unixSocket,
        int $port = 5432,
        array $queries = [],
        array $options = [],
        ?Closure $debug = null
    ): static {
        $driver = static::DRIVER;

        $adapter = $driver->adapter(
            $name,
            new UnixSocket($unixSocket, $port, $username, $password),
            $queries,
            $options,
            $debug
        );

        $version = $adapter->version();

        $dialect = $driver->dialect($version, $options);

        $schema = $driver->schema();

        return new static($adapter, $dialect, $schema);
    }
}
