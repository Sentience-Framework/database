<?php

namespace Sentience\Database\Queries\Objects;

class AlterColumn
{
    public function __construct(
        public string $column,
        public string $sql
    ) {
    }
}
