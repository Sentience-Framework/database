<?php

namespace Sentience\Database\Schemas;

use Sentience\Database\Databases\DatabaseInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Objects\Column;
use Sentience\Database\Queries\Objects\Index;
use Sentience\Database\Queries\Objects\Join;
use Sentience\Database\Queries\Objects\Type;
use Sentience\Database\Queries\Objects\WhereGroup;
use Sentience\Database\Queries\Query;

class SQLServerSchema extends SQLSchema
{
    public function columns(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $identityColumns = array_column(
            $database->select(['sys', 'identity_columns'])
                ->columns(['column_name' => ['sys', 'identity_columns', 'name']])
                ->whereEquals(
                    ['sys', 'identity_columns', 'object_id'],
                    Query::expressionf('object_id(%s)', $table)
                )
                ->execute()
                ->fetchAssocs(),
            'column_name'
        );

        return array_map(
            function (Column $column) use ($identityColumns): Column {
                $column->generatedByDefaultAsIdentity = in_array($column->name, $identityColumns);

                return $column;
            },
            parent::columns($database, $dialect, $table)
        );
    }

    public function indexes(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $indexes = $database->select(['sys', 'indexes'])
            ->columns([
                'index_name' => ['sys', 'indexes', 'name'],
                'column_name' => ['sys', 'columns', 'name'],
                'unique' => ['sys', 'indexes', 'is_unique']
            ])
            ->innerJoin(
                ['sys', 'index_columns'],
                fn (Join $join): Join => $join
                    ->on(
                        ['sys', 'index_columns', 'object_id'],
                        ['sys', 'indexes', 'object_id']
                    )
                    ->on(
                        ['sys', 'index_columns', 'index_id'],
                        ['sys', 'indexes', 'index_id']
                    )
            )
            ->innerJoin(
                ['sys', 'columns'],
                fn (Join $join): Join => $join
                    ->on(
                        ['sys', 'columns', 'object_id'],
                        ['sys', 'index_columns', 'object_id']
                    )
                    ->on(
                        ['sys', 'columns', 'column_id'],
                        ['sys', 'index_columns', 'column_id']
                    )
            )
            ->whereEquals(
                ['sys', 'indexes', 'object_id'],
                Query::expressionf('object_id(%s)', $table)
            )
            ->whereEquals(['sys', 'indexes', 'is_primary_key'], false)
            ->whereEquals(['sys', 'index_columns', 'is_included_column'], false)
            ->whereIsNotNull(['sys', 'indexes', 'name'])
            ->orderByAsc(['sys', 'indexes', 'name'])
            ->orderByAsc(['sys', 'index_columns', 'key_ordinal'])
            ->execute()
            ->fetchAssocs();

        $indexNames = [];
        $indexColumns = [];
        $indexUnique = [];

        foreach ($indexes as $index) {
            $indexName = $index['index_name'];
            $columnName = $index['column_name'];
            $unique = (bool) $index['unique'];

            if (!in_array($indexName, $indexNames)) {
                $indexNames[] = $indexName;
            }

            if (!array_key_exists($indexName, $indexColumns)) {
                $indexColumns[$indexName] = [];
            }

            $indexColumns[$indexName][] = $columnName;
            $indexUnique[$indexName] = $unique;
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
        return $whereGroup
            ->whereEquals('TABLE_CATALOG', Query::raw('db_name()'))
            ->whereEquals('TABLE_SCHEMA', Query::raw('schema_name()'));
    }

    protected function type(string $type, ?int $size): string|Type
    {
        $size = $size < 0 ? PHP_INT_MAX : $size;

        return match ($type) {
            'BIT',
            'SMALLINT' => new Type(TypeEnum::Bool),
            'TINYINT' => new Type(TypeEnum::Int, 32),
            'MONEY',
            'SMALLMONEY',
            'NUMERIC' => new Type(TypeEnum::Float, 64),
            'CHAR',
            'NCHAR',
            'NVARCHAR' => new Type(TypeEnum::String, $size ?? 255),
            'NTEXT' => new Type(TypeEnum::String, $size ?? PHP_INT_MAX),
            'DATETIME2',
            'DATETIMEOFFSET',
            'SMALLDATETIME' => new Type(TypeEnum::DateTime, $size ?? 0),
            default => parent::type($type, $size)
        };
    }
}
