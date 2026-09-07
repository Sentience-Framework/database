<?php

namespace Sentience\Database\Databases\SQLite;

use Closure;
use Sentience\Database\Databases\DatabaseAbstract;
use Sentience\Database\Driver;

class SQLiteDatabase extends DatabaseAbstract
{
    public const Driver DRIVER = Driver::SQLite;

    public static function file(
        string $file,
        array $queries = [],
        array $options = [],
        ?Closure $debug = null,
        bool $usePDOAdapter = false
    ): static {
        $driver = static::DRIVER;

        $adapter = $driver->adapter(
            $file,
            null,
            $queries,
            $options,
            $debug,
            $usePDOAdapter
        );

        $version = $adapter->version();

        $dialect = $driver->dialect($version, $options);

        $schema = $driver->schema();

        return new static($adapter, $dialect, $schema);
    }

    public static function memory(
        array $queries = [],
        array $options = [],
        ?Closure $debug = null,
        bool $usePDOAdapter = false
    ): static {
        return static::file(
            ':memory:',
            $queries,
            $options,
            $debug,
            $usePDOAdapter
        );
    }
}
