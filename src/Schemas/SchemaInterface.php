<?php

namespace Sentience\Database\Schemas;

use Sentience\Database\DatabaseInterface;
use Sentience\Database\Dialects\DialectInterface;

interface SchemaInterface
{
    public function tables(DatabaseInterface $database, DialectInterface $dialect): array;
    public function columns(DatabaseInterface $database, DialectInterface $dialect, string $table): array;
    public function primaryKeys(DatabaseInterface $database, DialectInterface $dialect, string $table): array;
    public function uniqueConstraints(DatabaseInterface $database, DialectInterface $dialect, string $table): array;
    public function foreignKeyConstraints(DatabaseInterface $database, DialectInterface $dialect, string $table): array;
    public function indexes(DatabaseInterface $database, DialectInterface $dialect, string $table): array;
}
