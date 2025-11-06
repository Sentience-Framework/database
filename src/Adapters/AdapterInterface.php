<?php

namespace Sentience\Database\Adapters;

use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Results\ResultInterface;

interface AdapterInterface
{
    public function connect(): void;
    public function disconnect(): void;
    public function connected(): bool;
    public function enableLazy(bool $disconnect = true): void;
    public function disableLazy(bool $connect = true): void;
    public function isLazy(): bool;
    public function version(): int|string;
    public function exec(string $query): void;
    public function query(string $query): ResultInterface;
    public function queryWithParams(DialectInterface $dialect, QueryWithParams $queryWithParams, bool $emulatePrepare): ResultInterface;
    public function beginTransaction(): void;
    public function commitTransaction(): void;
    public function rollbackTransaction(): void;
    public function inTransaction(): bool;
    public function lastInsertId(?string $name = null): null|int|string;
}
