<?php

namespace Sentience\Database\Databases;

use Closure;
use Sentience\Database\Driver;
use Sentience\Database\Sockets\NetworkSocket;

class SQLServerDatabase extends DatabaseAbstract
{
    public const Driver DRIVER = Driver::SQLSRV;

    public static function fromNetwork(
        string $name,
        string $username,
        ?string $password,
        string $host = 'localhost',
        int $port = 1433,
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

    public function informationSchemaTables(): array
    {
        return $this->select(['information_schema', 'tables'])
            ->whereEquals('table_type', 'BASE TABLE')
            ->execute()
            ->fetchAssocs();
    }

    public function spColumns(string $table): array
    {
        $query = sprintf(
            'SP_COLUMNS %s',
            $this->dialect->escapeIdentifier($table)
        );

        return $this->query($query)->fetchAssocs();
    }
}
