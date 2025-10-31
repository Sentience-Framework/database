<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Dialects\DialectInterface;

class CurrentTimestamp extends Expression
{
    public function __construct()
    {
    }

    public function sql(DialectInterface $dialect): string
    {
        return 'CURRENT_TIMESTAMP';
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
