<?php

namespace Sentience\Database\Adapters;

use Closure;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Driver;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Results\ResultInterface;

interface AdapterInterface
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
        ?Closure $debug
    ): static;

    public function version(): int|string;
    public function exec(string $query): void;
    public function query(string $query): ResultInterface;
    public function queryWithParams(DialectInterface $dialect, QueryWithParams $queryWithParams, bool $emulatePrepare): ResultInterface;
    public function beginTransaction(): void;
    public function commitTransaction(): void;
    public function rollbackTransaction(): void;
    public function inTransaction(): bool;
    public function lastInsertId(?string $name = null): null|int|string;
    public function disconnect(): void;
    public function reconnect(): void;
    public function isConnected(): bool;
}
