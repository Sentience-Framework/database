<?php

namespace Sentience\Database\Queries\Objects;

class RenameColumn
{
    public function __construct(
        public string $old,
        public string $new
    ) {
    }
}
