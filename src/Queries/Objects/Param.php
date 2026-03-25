<?php

namespace Sentience\Database\Queries\Objects;

use DateTimeInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Interfaces\Sql;
use Sentience\Database\Queries\SelectQuery;

class Param implements Sql
{
    public function __construct(protected null|bool|int|float|string|DateTimeInterface|SelectQuery|Sql $value)
    {
    }

    public function sql(DialectInterface $dialect): string
    {
        return '?';
    }

    public function params(DialectInterface $dialect): array
    {
        return [$this->value];
    }

    public function rawSql(DialectInterface $dialect): string
    {
        if ($this->value instanceof SelectQuery) {
            return $this->value->toSql();
        }

        if ($this->value instanceof Sql) {
            return $this->value->rawSql($dialect);
        }

        return $dialect->castToQuery($this->value);
    }
}
