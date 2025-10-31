<?php

namespace Sentience\Database\Queries\Objects;

class OnConflict
{
    public function __construct(
        public string|array $conflict,
        public ?array $updates
    ) {
    }
}
