<?php

namespace Sentience\Database\Databases\OCI;

use Closure;
use Sentience\Database\Databases\DatabaseAbstract;
use Sentience\Database\Driver;
use Sentience\Database\Sockets\NetworkSocket;

class OCIDatabase extends DatabaseAbstract
{
    public const Driver DRIVER = Driver::OCI;

    public static function network(
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

    public function userTables(): array
    {
        return $this->select('user_tables')
            ->whereNotIn('table_name', ['DUAL', 'USER_HISTORY$'])
            ->execute()
            ->fetchAssocs();
    }

    public function userTabColumns(string $table): array
    {
        return $this->select('user_tab_columns')
            ->whereEquals('table_name', $table)
            ->orderByAsc('column_id')
            ->execute()
            ->fetchAssocs();
    }
}
