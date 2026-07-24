<?php

namespace Sentience\Database;

use Closure;
use PDO;
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
        $adapter = $driver->adapter(
            $name,
            $socket,
            $queries,
            $options,
            $debug,
            $usePDOAdapter
        );

        $version = $adapter->version();

        $dialect = $driver->dialect($version, $options);

        return new static($adapter, $dialect);
    }

    public static function drivers(): array
    {
        $drivers = [];

        if (class_exists('PDO')) {
            foreach (PDO::getAvailableDrivers() as $pdoDriver) {
                $driver = Driver::tryFrom($pdoDriver);

                if (!$driver) {
                    continue;
                }

                $drivers[] = $driver;
            }

            if (in_array(Driver::MySQL, $drivers)) {
                $drivers[] = Driver::MariaDB;
            }
        }

        if (class_exists('mysqli')) {
            foreach ([Driver::MariaDB, Driver::MySQL] as $driver) {
                if (in_array($driver, $drivers)) {
                    continue;
                }

                $drivers[] = $driver;
            }
        }

        if (class_exists('SQLite3')) {
            if (!in_array(Driver::SQLite, $drivers)) {
                $drivers[] = Driver::SQLite;
            }
        }

        return $drivers;
    }
}
