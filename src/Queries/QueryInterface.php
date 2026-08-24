<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Queries\Objects\Statement;

interface QueryInterface
{
    public function toStatement(): array|Statement;
    public function toSql(): string|array;
    public function execute(bool $emulatePrepare = false): mixed;
}
