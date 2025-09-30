<?php

namespace Sentience\Database;

use Closure;
use Throwable;
use Sentience\Database\Adapters\AdapterInterface;
use Sentience\Database\Dialects\DialectInterface;
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
    protected AdapterInterface $adapter;
    protected DialectInterface $dialect;

    public function __construct(
        protected Driver $driver,
        protected string $host,
        protected int $port,
        protected string $name,
        protected string $username,
        protected string $password,
        protected array $queries,
        protected array $options,
        protected ?Closure $debug,
        bool $usePDOAdapter = false
    ) {
        $adapter = $driver->getAdapter(
            $host,
            $port,
            $name,
            $username,
            $password,
            $queries,
            $options,
            $debug,
            $usePDOAdapter
        );

        $dialect = $driver->getDialect();

        $this->adapter = $adapter;
        $this->dialect = $dialect;
    }

    public function query(string $query): void
    {
        $this->adapter->query($query);
    }

    public function prepared(string $query, array $params = []): ResultInterface
    {
        return $this->queryWithParams(new QueryWithParams($query, $params));
    }

    public function queryWithParams(QueryWithParams $queryWithParams): ResultInterface
    {
        return $this->adapter->queryWithParams($this->dialect, $queryWithParams);
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

    public function lastInsertId(?string $name = null): ?string
    {
        return $this->adapter->lastInsertId($name);
    }

    public function select(string|array|Alias|Raw $table): SelectQuery
    {
        return new SelectQuery($this, $this->dialect, $table);
    }

    public function insert(string|array|Alias|Raw $table = null): InsertQuery
    {
        return new InsertQuery($this, $this->dialect, $table);
    }

    public function update(string|array|Alias|Raw $table = null): UpdateQuery
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
}
