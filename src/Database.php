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
use Sentience\Database\Results\Result;
use Sentience\Database\Results\ResultInterface;

class Database
{
    protected bool $lazy = false;

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
        bool $usePdoAdapter = false
    ): static {
        if (!$driver->isSupportedBySentience()) {
            throw new DriverException('this driver requires ::pdo()');
        }

        $adapter = $driver->getAdapter(
            $host,
            $port,
            $name,
            $username,
            $password,
            $queries,
            $options,
            $debug,
            $usePdoAdapter
        );

        $version = $adapter->version();

        $dialect = $driver->getDialect($version);

        return new static($adapter, $dialect);
    }

    public static function pdo(
        Closure $connect,
        Driver $driver,
        array $queries,
        array $options,
        ?Closure $debug
    ): static {
        $adapter = new PDOAdapter(
            $connect,
            $driver,
            $queries,
            $options,
            $debug
        );

        $version = $adapter->version();

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
        $this->reconnectIfNotConnected();

        $this->adapter->exec($query);

        $this->disconnectIfLazyAndNotInTransaction();
    }

    public function query(string $query): ResultInterface
    {
        $this->reconnectIfNotConnected();

        $result = $this->adapter->query($query);

        if ($this->lazy) {
            $result = Result::fromInterface($result);
        }

        $this->disconnectIfLazyAndNotInTransaction();

        return $result;
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
        $this->reconnectIfNotConnected();

        $result = count($queryWithParams->params) > 0
            ? $this->adapter->queryWithParams($this->dialect, $queryWithParams, $emulatePrepare)
            : $this->adapter->query($queryWithParams->query);

        if ($this->lazy) {
            $result = Result::fromInterface($result);
        }

        $this->disconnectIfLazyAndNotInTransaction();

        return $result;
    }

    public function beginTransaction(): void
    {
        $this->adapter->beginTransaction();
    }

    public function commitTransaction(): void
    {
        $this->adapter->commitTransaction();
    }

    public function rollbackTransaction(): void
    {
        $this->adapter->rollbackTransaction();
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

    public function enableLazy(): static
    {
        $this->lazy = true;

        return $this;
    }

    public function disableLazy(): static
    {
        $this->lazy = false;

        $this->adapter->reconnect();

        return $this;
    }

    protected function reconnectIfNotConnected(): void
    {
        if (!$this->lazy) {
            return;
        }

        if ($this->adapter->isConnected()) {
            return;
        }

        $this->adapter->reconnect();
    }

    protected function disconnectIfLazyAndNotInTransaction(): void
    {
        if (!$this->lazy) {
            return;
        }

        if ($this->inTransaction()) {
            return;
        }

        $this->adapter->disconnect();
    }
}
