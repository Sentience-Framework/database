<?php

namespace Sentience\Database\Schemas;

use Sentience\Database\DatabaseInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Objects\Index;
use Sentience\Database\Queries\Objects\Type;
use Sentience\Database\Queries\Objects\WhereGroup;
use Sentience\Database\Queries\Query;

class MySQLSchema extends SQLSchema
{
    public function indexes(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $indexes = $database->select(['INFORMATION_SCHEMA', 'STATISTICS'])
            ->columns(['INDEX_NAME', 'COLUMN_NAME', 'NON_UNIQUE'])
            ->whereGroup(fn (WhereGroup $whereGroup): WhereGroup => $this->databaseSchema($whereGroup))
            ->whereEquals('TABLE_NAME', $table)
            ->whereNotContains('INDEX_NAME', 'PRIMARY', true)
            ->execute()
            ->fetchAssocs();

        $indexNames = [];
        $indexColumns = [];
        $indexUnique = [];

        foreach ($indexes as $index) {
            $indexName = $index['INDEX_NAME'];
            $columnName = $index['COLUMN_NAME'];
            $nonUnique = (bool) $index['NON_UNIQUE'];

            if (!in_array($indexName, $indexNames)) {
                $indexNames[] = $indexName;
            }

            if (!array_key_exists($indexName, $indexColumns)) {
                $indexColumns[$indexName] = [];
            }

            $indexColumns[$indexName][] = $columnName;
            $indexUnique[$indexName] = !$nonUnique;
        }

        return array_map(
            fn (string $name): Index => new Index(
                $name,
                $indexColumns[$name],
                $indexUnique[$name]
            ),
            $indexNames
        );
    }

    protected function databaseSchema(WhereGroup $whereGroup): WhereGroup
    {
        return $whereGroup->whereEquals('TABLE_SCHEMA', Query::raw('database()'));
    }

    protected function type(string $type, ?int $size): string|Type
    {
        return match ($type) {
            'TINYINT' => new Type(TypeEnum::Bool),
            'DOUBLE' => new Type(TypeEnum::Float, 64),
            'TEXT' => new Type(TypeEnum::String, $size ?? 65535),
            'MEDIUMTEXT' => new Type(TypeEnum::String, $size ?? 16777215),
            'LONGTEXT' => new Type(TypeEnum::String, $size ?? 4294967295),
            'DATETIME' => new Type(TypeEnum::DateTime, $size ?? 0),
            default => parent::type($type, $size)
        };
    }

    protected function isIdentity(array $column): bool
    {
        $column = array_change_key_case($column, CASE_LOWER);

        return (bool) preg_match('/.*auto_increment.*/i', (string) ($column['extra'] ?? ''));
    }
}
