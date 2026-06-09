<?php

namespace Sentience\Database\Databases\Informix;

use Closure;
use Sentience\Database\Databases\DatabaseAbstract;
use Sentience\Database\Driver;
use Sentience\Database\Queries\Objects\Join;
use Sentience\Database\Queries\Query;
use Sentience\Database\Sockets\NetworkSocket;

class InformixDatabase extends DatabaseAbstract
{
    public const Driver DRIVER = Driver::INFORMIX;

    public static function network(
        string $name,
        string $username,
        ?string $password,
        string $host = 'localhost',
        int $port = 9088,
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
        $result = $this->select('systables')
            ->columns(['tabname'])
            ->whereEquals('tabtype', 'T')
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
        return $this->select('systables')
            ->columns([['syscolumns', Query::raw('*')]])
            ->innerJoin(
                'syscolumns',
                fn (Join $join) => $join->on(
                    ['systables', 'tabid'],
                    ['syscolumns', 'tabid']
                )
            )
            ->whereEquals(['systables', 'tabname'], $table)
            ->orderByAsc(['syscolumns', 'colno'])
            ->execute()
            ->fetchAssocs();
    }
}
