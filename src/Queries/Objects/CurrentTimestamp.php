<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Interfaces\Sql;

class CurrentTimestamp implements Sql
{
    public function __construct(protected ?int $precision)
    {
    }

    public function sql(DialectInterface $dialect): string
    {
        $sql = 'CURRENT_TIMESTAMP';

        if ($this->precision) {
            $sql .= sprintf('(%d)', abs($this->precision));
        }

        return $sql;
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
