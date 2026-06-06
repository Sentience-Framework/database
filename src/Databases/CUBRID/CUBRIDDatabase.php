<?php

namespace Sentience\Database\Databases\CUBRID;

use Closure;
use Sentience\Database\Databases\DatabaseAbstract;
use Sentience\Database\Driver;
use Sentience\Database\Sockets\NetworkSocket;

class CUBRIDDatabase extends DatabaseAbstract
{
    public const Driver DRIVER = Driver::CUBRID;

    public static function network(
        string $name,
        string $username,
        ?string $password,
        string $host = 'localhost',
        int $port = 33000,
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

        return new static($adapter, $dialect);
    }

    public function informationSchemaTables(): array
    {
        return $this->select(['information_schema', 'tables'])
            ->whereEquals('table_type', 'BASE TABLE')
            ->execute()
            ->fetchAssocs();
    }

    public function informationSchemaColumns(string $table): array
    {
        return $this->select(['information_schema', 'columns'])
            ->whereEquals('TABLE_NAME', $table)
            ->execute()
            ->fetchAssocs();
    }
}
