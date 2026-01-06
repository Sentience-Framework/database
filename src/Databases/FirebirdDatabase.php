<?php

namespace Sentience\Database\Databases;

use Closure;
use Sentience\Database\Driver;
use Sentience\Database\Queries\Objects\Join;
use Sentience\Database\Queries\Query;
use Sentience\Database\Sockets\NetworkSocket;

class FirebirdDatabase extends DatabaseAbstract
{
    public const Driver DRIVER = Driver::FIREBIRD;

    public static function fromNetwork(
        string $name,
        string $username,
        ?string $password,
        string $host = 'localhost',
        int $port = 3050,
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

    public function lastInsertId(?string $name = null): null|int|string
    {
        return null;
    }

    public function rdbRelationsTables(): array
    {
        return $this->select(Query::raw('RDB$RELATIONS'))
            ->columns([Query::raw('RDB$RELATION_NAME')])
            ->where('COALESCE(RDB$SYSTEM_FLAG, 0) = 0')
            ->where('RDB$RELATION_TYPE = 0')
            ->execute()
            ->fetchAssocs();
    }

    public function rdbRelationFields(string $table): array
    {
        return $this->select(Query::alias(Query::raw('RDB$RELATION_FIELDS'), 'R'))
            ->columns([
                Query::alias(Query::raw('R.RDB$FIELD_NAME'), 'field_name'),
                Query::alias(Query::raw('F.RDB$FIELD_TYPE'), 'field_type'),
                Query::alias(Query::raw('F.RDB$FIELD_LENGTH'), 'field_length'),
                Query::alias(Query::raw('CSET.RDB$CHARACTER_SET_NAME'), 'field_charset')
            ])
            ->leftJoin(
                Query::alias(Query::raw('RDB$FIELDS'), 'F'),
                fn (Join $join): Join => $join->where('R.RDB$FIELD_SOURCE = F.RDB$FIELD_NAME')
            )
            ->leftJoin(
                Query::alias(Query::raw('RDB$CHARACTER_SETS'), 'CSET'),
                fn (Join $join): Join => $join->where('F.RDB$CHARACTER_SET_ID = CSET.RDB$CHARACTER_SET_ID')
            )
            ->where('R.RDB$RELATION_NAME = ?', [$table])
            ->orderByAsc(Query::raw('R.RDB$FIELD_POSITION'))
            ->execute()
            ->fetchAssocs();
    }
}
