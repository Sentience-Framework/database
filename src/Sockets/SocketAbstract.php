<?php

namespace Sentience\Database\Sockets;

abstract class SocketAbstract
{
    public function __construct(
        public string $host,
        public ?int $port,
        public ?string $username = null,
        public ?string $password = null
    ) {
    }
}
