<?php

namespace Sentience\Database;

use Closure;
use Throwable;
use Sentience\Database\Adapters\AdapterInterface;
use Sentience\Database\Adapters\PDOAdapter;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Exceptions\DriverException;
use Sentience\Database\Queries\AlterTableQuery;
use Sentience\Database\Queries\CreateTableQuery;
use Sentience\Database\Queries\DeleteQuery;
use Sentience\Database\Queries\DropTableQuery;
use Sentience\Database\Queries\InsertQuery;
use Sentience\Database\Queries\Objects\Alias;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Queries\SelectQuery;
use Sentience\Database\Queries\UpdateQuery;
use Sentience\Database\Results\ResultInterface;

class Database
{
    public static function connect(
        Driver $driver,
        string $host,
        int $port,
        string $name,
        string $username,
        string $password,
        array $queries,
        array $options,
        ?Closure $debug,
        bool $usePdoAdapter = false,
        bool $lazy = false,
        int $retries = 0
    ): static {
        if (!$driver->isSupportedBySentience()) {
            throw new DriverException('this driver requires ::pdo()');
        }

        for ($i = 0; $i <= $retries; $i++) {
            try {
                $adapter = $driver->getAdapter(
                    $host,
                    $port,
                    $name,
                    $username,
                    $password,
                    $queries,
                    $options,
                    $debug,
                    $usePdoAdapter,
                    $lazy
                );
            } catch (Throwable $exception) {
                if ($i == $retries) {
                    throw $exception;
                }

                continue;
            }
        }

        $version = array_key_exists(AdapterInterface::OPTIONS_VERSION, $options)
            ? (string) $options[AdapterInterface::OPTIONS_VERSION]
            : $adapter->version();

        $dialect = $driver->getDialect($version);

        return new static($adapter, $dialect);
    }

    public static function pdo(
        Closure $connect,
        Driver $driver,
        array $queries,
        array $options,
        ?Closure $debug,
        bool $lazy = false,
        int $retries = 0
    ): static {
        for ($i = 0; $i <= $retries; $i++) {
            try {
                $adapter = new PDOAdapter(
                    $connect,
                    $driver,
                    $queries,
                    $options,
                    $debug,
                    $lazy
                );
            } catch (Throwable $exception) {
                if ($i == $retries) {
                    throw $exception;
                }

                continue;
            }
        }

        $version = array_key_exists(AdapterInterface::OPTIONS_VERSION, $options)
            ? (string) $options[AdapterInterface::OPTIONS_VERSION]
            : $adapter->version();

        $dialect = $driver->getDialect($version);

        return new static($adapter, $dialect);
    }

    public function __construct(
        protected AdapterInterface $adapter,
        protected DialectInterface $dialect
    ) {
    }

    public function exec(string $query): void
    {
        $this->adapter->exec($this->dialect, $query);
    }

    public function query(string $query): ResultInterface
    {
        return $this->adapter->query($this->dialect, $query);
    }

    public function prepared(string $query, array $params = [], bool $emulatePrepare = false): ResultInterface
    {
        return $this->queryWithParams(
            new QueryWithParams($query, $params),
            $emulatePrepare
        );
    }

    public function queryWithParams(QueryWithParams $queryWithParams, bool $emulatePrepare = false): ResultInterface
    {
        return count($queryWithParams->params) > 0
            ? $this->adapter->queryWithParams($this->dialect, $queryWithParams, $emulatePrepare)
            : $this->adapter->query($this->dialect, $queryWithParams->query);
    }

    public function beginTransaction(): void
    {
        $this->adapter->beginTransaction($this->dialect);
    }

    public function commitTransaction(): void
    {
        $this->adapter->commitTransaction($this->dialect);
    }

    public function rollbackTransaction(): void
    {
        $this->adapter->rollbackTransaction($this->dialect);
    }

    public function inTransaction(): bool
    {
        return $this->adapter->inTransaction();
    }

    public function transactionInCallback(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $return = $callback($this);

            $this->commitTransaction();

            return $return;
        } catch (Throwable $exception) {
            $this->rollbackTransaction();

            throw $exception;
        }
    }

    public function lastInsertId(?string $name = null): null|int|string
    {
        return $this->adapter->lastInsertId($name);
    }

    public function select(string|array|Alias|Raw $table): SelectQuery
    {
        return new SelectQuery($this, $this->dialect, $table);
    }

    public function insert(string|array|Alias|Raw $table): InsertQuery
    {
        return new InsertQuery($this, $this->dialect, $table);
    }

    public function update(string|array|Alias|Raw $table): UpdateQuery
    {
        return new UpdateQuery($this, $this->dialect, $table);
    }

    public function delete(string|array|Alias|Raw $table): DeleteQuery
    {
        return new DeleteQuery($this, $this->dialect, $table);
    }

    public function createTable(string|array|Alias|Raw $table): CreateTableQuery
    {
        return new CreateTableQuery($this, $this->dialect, $table);
    }

    public function alterTable(string|array|Alias|Raw $table): AlterTableQuery
    {
        return new AlterTableQuery($this, $this->dialect, $table);
    }

    public function dropTable(string|array|Alias|Raw $table): DropTableQuery
    {
        return new DropTableQuery($this, $this->dialect, $table);
    }

    public function ping(bool $reconnect = false): bool
    {
        return $this->adapter->ping($this->dialect, $reconnect);
    }

    public function enableLazy(bool $disconnect = true): void
    {
        $this->adapter->enableLazy($disconnect);
    }

    public function disableLazy(bool $connect = true): void
    {
        $this->adapter->disableLazy($connect);
    }

    public function isLazy(): bool
    {
        return $this->adapter->isLazy();
    }
}
