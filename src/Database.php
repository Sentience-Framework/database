<?php

namespace Sentience\Database;

use Closure;
use PDO;
use Sentience\Database\Adapters\MySQLiAdapter;
use Sentience\Database\Adapters\PDOAdapter;
use Sentience\Database\Adapters\SQLite3Adapter;
use Sentience\Database\Databases\DatabaseAbstract;
use Sentience\Database\Sockets\SocketAbstract;

class Database extends DatabaseAbstract
{
    public static function connect(
        DriverInterface $driver,
        string $name,
        ?SocketAbstract $socket = null,
        array $queries = [],
        array $options = [],
        ?Closure $debug = null,
        bool $usePDOAdapter = false
    ): static {
        $adapter = $driver->getAdapter(
            $name,
            $socket,
            $queries,
            $options,
            $debug,
            $usePDOAdapter
        );

        $version = $adapter->version();

        $dialect = $driver->getDialect($version);

        return new static($adapter, $dialect);
    }

    public static function getAvailableDrivers(): array
    {
        $availableDrivers = [];

        if (class_exists(PDOAdapter::PDO)) {
            foreach (PDO::getAvailableDrivers() as $pdoDriver) {
                $driver = Driver::tryFrom($pdoDriver);

                if (!$driver) {
                    continue;
                }

                $availableDrivers[] = $driver;
            }

            if (in_array(Driver::MYSQL, $availableDrivers)) {
                $availableDrivers[] = Driver::MARIADB;
            }
        }

        if (class_exists(MySQLiAdapter::MYSQLI)) {
            foreach ([Driver::MYSQL, Driver::MARIADB] as $driver) {
                if (in_array($driver, $availableDrivers)) {
                    continue;
                }

                $availableDrivers[] = $driver;
            }
        }

        if (class_exists(SQLite3Adapter::SQLITE3)) {
            if (!in_array(Driver::SQLITE, $availableDrivers)) {
                $availableDrivers[] = Driver::SQLITE;
            }
        }

        return $availableDrivers;
    }
}
