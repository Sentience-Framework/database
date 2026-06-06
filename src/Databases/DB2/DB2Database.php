<?php

namespace Sentience\Database\Databases\DB2;

use Closure;
use Sentience\Database\Databases\DatabaseAbstract;
use Sentience\Database\Driver;
use Sentience\Database\Sockets\NetworkSocket;

class DB2Database extends DatabaseAbstract
{
    public const Driver DRIVER = Driver::DB2;

    public static function network(
        string $name,
        string $username,
        ?string $password,
        string $host = 'localhost',
        int $port = 50000,
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

    public function sysTables(): array
    {
        $result = $this->select(['syscat', 'tables'])
            ->columns(['table_name' => 'TABNAME'])
            ->whereEquals('TYPE', 'T')
            ->execute();

        $tables = [];

        while ($table = $result->scalar()) {
            if (!$table) {
                break;
            }

            $tables[] = $table;
        }

        return $tables;
    }

    public function sysColumns(string $table): array
    {
        return $this->select(['syscat', 'columns'])
            ->whereEquals('TABNAME', $table)
            ->orderByAsc('COLNO')
            ->execute()
            ->fetchAssocs();
    }
}
