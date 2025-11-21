<?php

namespace Sentience\Database\Sockets;

interface SocketInterface
{
    public function address(): string|array;
    public function username(): ?string;
    public function password(): ?string;
}
