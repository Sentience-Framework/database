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
use Sentience\Database\Queries\Objects\SubQuery;
use Sentience\Database\Queries\QueryFactory;
use Sentience\Database\Queries\SelectQuery;
use Sentience\Database\Queries\UpdateQuery;
use Sentience\Database\Results\ResultInterface;
use Sentience\Database\Sockets\SocketAbstract;

class Database implements DatabaseInterface
{
    public static function connect(
        Driver $driver,
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
            $this->adapter->beginTransaction($this->dialect, $name);

            return;
        }

        $name ??= sprintf(
            'savepoint_%d',
            count($this->savepoints) + 1
        );

        $this->savepoints[] = $name;

        $this->adapter->beginSavepoint($this->dialect, $name);
    }

    public function commitTransaction(bool $releaseSavepoints = false, ?string $name = null): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        if ($releaseSavepoints || count($this->savepoints) == 0) {
            $this->savepoints = [];

            $this->adapter->commitTransaction($this->dialect, $name);

            return;
        }

        $this->adapter->commitSavepoint(
            $this->dialect,
            array_pop($this->savepoints)
        );
    }

    public function rollbackTransaction(bool $releaseSavepoints = false, ?string $name = null): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        if ($releaseSavepoints || count($this->savepoints) == 0) {
            $this->savepoints = [];

            $this->adapter->rollbackTransaction($this->dialect, $name);

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

    public function transaction(callable $callback, bool $releaseSavepoints = false, ?string $name = null): mixed
    {
        $this->beginTransaction($name);

        try {
            $result = $callback($this);

            $this->commitTransaction($releaseSavepoints, $name);

            return $result;
        } catch (Throwable $exception) {
            $this->rollbackTransaction($releaseSavepoints, $name);

            throw $exception;
        }
    }

    public function lastInsertId(?string $name = null): null|int|string
    {
        return $this->adapter->lastInsertId($name);
    }

    public function select(string|array|Alias|Raw|SubQuery $table): SelectQuery
    {
        return new SelectQuery($this, $this->dialect, $table);
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

    public function table(string|array|Raw $table): QueryFactory
    {
        return new QueryFactory($this, $this->dialect, $table);
    }
}
