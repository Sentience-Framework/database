<?php

namespace Sentience\Database;

use Closure;
use Throwable;
use Sentience\Database\Adapters\AdapterInterface;
use Sentience\Database\Adapters\PDOAdapter;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\AlterTableQuery;
use Sentience\Database\Queries\CreateTableQuery;
use Sentience\Database\Queries\DeleteQuery;
use Sentience\Database\Queries\DropTableQuery;
use Sentience\Database\Queries\InsertQuery;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Queries\Query;
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
        bool $lazy = false
    ): static {
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

        $version = ($options[AdapterInterface::OPTIONS_VERSION] ?? null)
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
        bool $lazy = false
    ): static {
        $adapter = new PDOAdapter(
            $connect,
            $driver,
            $queries,
            $options,
            $debug,
            $lazy
        );

        $version = ($options[AdapterInterface::OPTIONS_VERSION] ?? null)
            ? (string) $options[AdapterInterface::OPTIONS_VERSION]
            : $adapter->version();

        $dialect = $driver->getDialect($version);

        return new static($adapter, $dialect);
    }

    protected array $savepoints = [];

    public function __construct(
        protected AdapterInterface $adapter,
        protected DialectInterface $dialect
    ) {
    }

    public function exec(string $query): void
    {
        $this->adapter->exec($query);
    }

    public function query(string $query): ResultInterface
    {
        return $this->adapter->query($query);
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
            : $this->adapter->query($queryWithParams->query);
    }

    public function beginTransaction(?string $name = null): void
    {
        if (!$this->inTransaction()) {
            $this->adapter->beginTransaction($this->dialect);

            return;
        }

        $name = !$name ?
            sprintf(
                'savepoint_%d',
                count($this->savepoints) + 1
            )
            : $name;

        $this->savepoints[] = $name;

        $this->adapter->beginSavepoint($this->dialect, $name);
    }

    public function commitTransaction(bool $releaseSavepoints = false): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        if ($releaseSavepoints || count($this->savepoints) == 0) {
            $this->adapter->commitTransaction($this->dialect);

            return;
        }

        $this->adapter->commitSavepoint(
            $this->dialect,
            array_pop($this->savepoints)
        );
    }

    public function rollbackTransaction(bool $releaseSavepoints = false): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        if ($releaseSavepoints || count($this->savepoints) == 0) {
            $this->adapter->rollbackTransaction($this->dialect);

            return;
        }

        $this->adapter->rollbackSavepoint(
            $this->dialect,
            array_pop($this->savepoints)
        );
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

    public function select(string|array|Raw|SelectQuery $table, ?string $alias = null): SelectQuery
    {
        return new SelectQuery($this, $this->dialect, $alias ? Query::alias($table, $alias) : $table);
    }

    public function insert(string|array|Raw $table): InsertQuery
    {
        return new InsertQuery($this, $this->dialect, $table);
    }

    public function update(string|array|Raw $table): UpdateQuery
    {
        return new UpdateQuery($this, $this->dialect, $table);
    }

    public function delete(string|array|Raw $table): DeleteQuery
    {
        return new DeleteQuery($this, $this->dialect, $table);
    }

    public function createTable(string|array|Raw $table): CreateTableQuery
    {
        return new CreateTableQuery($this, $this->dialect, $table);
    }

    public function alterTable(string|array|Raw $table): AlterTableQuery
    {
        return new AlterTableQuery($this, $this->dialect, $table);
    }

    public function dropTable(string|array|Raw $table): DropTableQuery
    {
        return new DropTableQuery($this, $this->dialect, $table);
    }

    public function ping(bool $reconnect = false): bool
    {
        return $this->adapter->ping($reconnect);
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
