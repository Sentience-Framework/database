<?php

namespace Sentience\Database\Databases\OCI;

use Closure;
use Sentience\Database\Databases\DatabaseAbstract;
use Sentience\Database\Driver;
use Sentience\Database\Sockets\NetworkSocket;

class OCIDatabase extends DatabaseAbstract
{
    public const Driver DRIVER = Driver::OCI;

    public static function fromNetwork(
        string $name,
        string $username,
        ?string $password,
        string $host = 'localhost',
        int $port = 1521,
        array $queries = [],
        array $options = [],
        ?Closure $debug = null
    ): static {
        $driver = static::DRIVER;

        $adapter = $driver->getAdapter(
            $name,
            new NetworkSocket($host, $port, $username, $password),
            $queries,
            $options,
            $debug
        );

        $version = $adapter->version();

        $dialect = $driver->getDialect($version);

        return new static($adapter, $dialect);
    }
}
