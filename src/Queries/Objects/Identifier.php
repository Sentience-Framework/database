<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Interfaces\Sql;

class Identifier implements Sql
{
    public function __construct(protected string|array $identifier)
    {
    }

    public function sql(DialectInterface $dialect): string
    {
        return $dialect->escapeIdentifier($this->identifier);
    }

    public function params(DialectInterface $dialect): array
    {
        return [];
    }

    public function rawSql(DialectInterface $dialect): string
    {
        return $this->sql($dialect);
    }
}
