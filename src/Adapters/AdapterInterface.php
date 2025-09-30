<?php

namespace Sentience\Database\Adapters;

use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Results\ResultInterface;

interface AdapterInterface
{
    public function query(string $query): void;
    public function queryWithParams(DialectInterface $dialect, QueryWithParams $queryWithParams): ResultInterface;
    public function beginTransaction(): void;
    public function commitTransaction(): void;
    public function rollbackTransaction(): void;
    public function inTransaction(): bool;
    public function lastInsertId(?string $name = null): ?string;
}
