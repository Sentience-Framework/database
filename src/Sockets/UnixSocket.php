<?php

namespace Sentience\Database\Sockets;

class UnixSocket extends SocketAbstract
{
    public function __construct(
        protected string $unixSocket,
        ?string $username = null,
        ?string $password = null
    ) {
        parent::__construct($username, $password);
    }

    public function address(): string
    {
        return $this->unixSocket;
    }
}
