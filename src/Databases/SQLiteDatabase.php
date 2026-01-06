<?php

namespace Sentience\Database\Databases;

use Closure;
use Sentience\Database\Driver;

class SQLiteDatabase extends DatabaseAbstract
{
    public const Driver DRIVER = Driver::SQLITE;

    public static function fromFile(
        string $name,
        array $queries = [],
        array $options = [],
        ?Closure $debug = null,
        bool $usePDOAdapter = false
    ): static {
        $driver = static::DRIVER;

        $adapter = $driver->getAdapter(
            $name,
            null,
            $queries,
            $options,
            $debug,
            $usePDOAdapter
        );

        $version = $adapter->version();

        $dialect = $driver->getDialect($version);

        return new static($adapter, $dialect);
    }

    public function sqliteMasterTables(): array
    {
        return $this->select('sqlite_master')
            ->whereEquals('type', 'table')
            ->execute()
            ->fetchAssocs();
    }

    public function pragmaTableInfo(string $table): array
    {
        $query = sprintf(
            'PRAGMA table_info(%s)',
            $this->dialect->escapeIdentifier($table)
        );

        return $this->query($query)->fetchAssocs();
    }
}
