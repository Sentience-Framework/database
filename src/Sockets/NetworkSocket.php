<?php

namespace Sentience\Database\Sockets;

class NetworkSocket extends SocketAbstract
{
    public function __construct(string $host, int $port, ?string $username = null, ?string $password = null)
    {
        parent::__construct($host, $port, $username, $password);
    }
}
