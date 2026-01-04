<?php

namespace Sentience\Database\Databases;

use Sentience\Database\Queries\AlterTableQuery;
use Sentience\Database\Queries\CreateTableQuery;
use Sentience\Database\Queries\DeleteQuery;
use Sentience\Database\Queries\DropTableQuery;
use Sentience\Database\Queries\InsertQuery;
use Sentience\Database\Queries\Objects\Alias;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Queries\Objects\SubQuery;
use Sentience\Database\Queries\SelectQuery;
use Sentience\Database\Queries\UpdateQuery;
use Sentience\Database\Results\ResultInterface;

interface DatabaseInterface
{
    public function exec(string $query): void;
    public function query(string $query): ResultInterface;
    public function prepared(string $query, array $params = [], bool $emulatePrepare = false): ResultInterface;
    public function queryWithParams(QueryWithParams $queryWithParams, bool $emulatePrepare = false): ResultInterface;
    public function beginTransaction(?string $name = null): void;
    public function commitTransaction(bool $releaseSavepoints = false, ?string $name = null): void;
    public function rollbackTransaction(bool $releaseSavepoints = false, ?string $name = null): void;
    public function inTransaction(): bool;
    public function transaction(callable $callback, bool $releaseSavepoints = false, ?string $name = null): mixed;
    public function lastInsertId(?string $name = null): null|int|string;
    public function select(string|array|Alias|Raw|SubQuery $table): SelectQuery;
    public function insert(string|array|Raw $table): InsertQuery;
    public function update(string|array|Raw $table): UpdateQuery;
    public function delete(string|array|Raw $table): DeleteQuery;
    public function createTable(string|array|Raw $table): CreateTableQuery;
    public function alterTable(string|array|Raw $table): AlterTableQuery;
    public function dropTable(string|array|Raw $table): DropTableQuery;
}
