<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Interfaces\Sql;

class Raw implements Sql
{
    public function __construct(protected string $sql)
    {
    }

    public function sql(DialectInterface $dialect): string
    {
        return $this->sql;
    }

    public function params(DialectInterface $dialect): array
    {
        return [];
    }

    public function rawSql(DialectInterface $dialect): string
    {
        return $this->sql;
    }
}
