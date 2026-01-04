<?php

namespace Sentience\Database\Databases;

use Closure;
use Sentience\Database\Driver;

class SQLiteDatabase extends DatabaseAbstract
{
    public static function fromFile(
        string $name,
        array $queries = [],
        array $options = [],
        ?Closure $debug = null,
        bool $usePDOAdapter = false
    ): static {
        $driver = Driver::SQLITE;

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
}
