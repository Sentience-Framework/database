<?php

namespace Sentience\Database\Queries\Interfaces;

use Sentience\Database\Dialects\DialectInterface;

interface Sql
{
    public function sql(DialectInterface $dialect): string;
    public function params(DialectInterface $dialect): array;
    public function rawSql(DialectInterface $dialect): string;
}
