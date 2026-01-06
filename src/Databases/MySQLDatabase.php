<?php

namespace Sentience\Database\Databases;

use Closure;
use Sentience\Database\Driver;
use Sentience\Database\Sockets\NetworkSocket;
use Sentience\Database\Sockets\UnixSocket;

class MySQLDatabase extends DatabaseAbstract
{
    public const Driver DRIVER = Driver::MYSQL;

    public static function fromNetwork(
        string $name,
        string $username,
        ?string $password,
        string $host = 'localhost',
        int $port = 3306,
        array $queries = [],
        array $options = [],
        ?Closure $debug = null,
        bool $usePDOAdapter = false
    ): static {
        $driver = static::DRIVER;

        $adapter = $driver->getAdapter(
            $name,
            new NetworkSocket($host, $port, $username, $password),
            $queries,
            $options,
            $debug,
            $usePDOAdapter
        );

        $version = $adapter->version();

        $dialect = $driver->getDialect($version);

        return new static($adapter, $dialect);
    }

    public static function fromUnixSocket(
        string $name,
        string $username,
        ?string $password,
        string $unixSocket,
        array $queries = [],
        array $options = [],
        ?Closure $debug = null,
        bool $usePDOAdapter = false
    ): static {
        $driver = static::DRIVER;

        $adapter = $driver->getAdapter(
            $name,
            new UnixSocket($unixSocket, null, $username, $password),
            $queries,
            $options,
            $debug,
            $usePDOAdapter
        );

        $version = $adapter->version();

        $dialect = $driver->getDialect($version);

        return new static($adapter, $dialect);
    }

    public function showTables(): array
    {
        $result = $this->query('SHOW TABLES');

        $tables = [];

        while ($table = $result->scalar()) {
            if (!$table) {
                break;
            }

            $tables[] = $table;
        }

        return $tables;
    }

    public function describeTable(string $table): array
    {
        $query = sprintf(
            'DESCRIBE %s',
            $this->dialect->escapeIdentifier($table)
        );

        return $this->query($query)->fetchAssocs();
    }
}
