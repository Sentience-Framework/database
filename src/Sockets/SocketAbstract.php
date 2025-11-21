<?php

namespace Sentience\Database\Sockets;

abstract class SocketAbstract implements SocketInterface
{
    public function __construct(
        protected ?string $username = null,
        protected ?string $password = null
    ) {
    }

    public function username(): ?string
    {
        return $this->username;
    }

    public function password(): ?string
    {
        return $this->password;
    }
}
