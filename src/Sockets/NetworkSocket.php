<?php

namespace Sentience\Database\Sockets;

class NetworkSocket extends SocketAbstract
{
    public function __construct(
        protected string $host,
        protected int $port,
        ?string $username = null,
        ?string $password = null
    ) {
        parent::__construct($username, $password);
    }

    public function address(): array
    {
        return [$this->host, $this->port];
    }
}
