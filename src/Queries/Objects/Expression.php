<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Interfaces\Sql;

class Expression implements Sql
{
    public function __construct(
        protected string $sql,
        protected array $params
    ) {
    }

    public function sql(DialectInterface $dialect): string
    {
        return $this->sql;
    }

    public function params(DialectInterface $dialect): array
    {
        return $this->params;
    }

    public function rawSql(DialectInterface $dialect): string
    {
        $statement = new Statement(
            $this->sql($dialect),
            $this->params($dialect)
        );

        return $statement->toSql($dialect);
    }
}
