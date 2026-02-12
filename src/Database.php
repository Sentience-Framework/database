<?php

namespace Sentience\Database;

use Closure;
use Sentience\Database\Databases\DatabaseAbstract;
use Sentience\Database\Sockets\SocketAbstract;

class Database extends DatabaseAbstract
{
    public static function connect(
        DriverInterface $driver,
        string $name,
        ?SocketAbstract $socket = null,
        array $queries = [],
        array $options = [],
        ?Closure $debug = null,
        bool $usePDOAdapter = false
    ): static {
        $adapter = $driver->getAdapter(
            $name,
            $socket,
            $queries,
            $options,
            $debug,
            $usePDOAdapter
        );

        $version = $adapter->version();

        $dialect = $driver->getDialect($version);

        return new static($adapter, $dialect);
    }
}
