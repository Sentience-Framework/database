<?php

namespace Sentience\Database;

use Closure;
use Sentience\Database\Adapters\AdapterInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Sockets\SocketAbstract;

interface DriverInterface
{
    public function driver(): string;

    public function adapter(
        string $name,
        ?SocketAbstract $socket,
        array $queries,
        array $options,
        ?Closure $debug,
        bool $usePDOAdapter = false
    ): AdapterInterface;

    public function dialect(int|string $version, array $options = []): DialectInterface;
}
