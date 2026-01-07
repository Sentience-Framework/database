<?php

namespace Sentience\Database\Databases\PgSQL;

use Closure;
use Sentience\Database\Databases\DatabaseAbstract;
use Sentience\Database\Driver;
use Sentience\Database\Sockets\NetworkSocket;
use Sentience\Database\Sockets\UnixSocket;

class PgSQLDatabase extends DatabaseAbstract
{
    public const Driver DRIVER = Driver::PGSQL;

    public static function fromNetwork(
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

    public static function fromUnixSocket(
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

        $adapter = $driver->getAdapter(
            $name,
            new UnixSocket($unixSocket, $port, $username, $password),
            $queries,
            $options,
            $debug
        );

        $version = $adapter->version();

        $dialect = $driver->getDialect($version);

        return new static($adapter, $dialect);
    }

    public function informationSchemaTables(): array
    {
        return $this->select(['information_schema', 'tables'])
            ->whereNotIn('table_schema', ['pg_catalog', 'information_schema'])
            ->execute()
            ->fetchAssocs();
    }

    public function informationSchemaColumns(string $table): array
    {
        return $this->select(['information_schema', 'columns'])
            ->whereEquals('table_name', $table)
            ->execute()
            ->fetchAssocs();
    }
}
