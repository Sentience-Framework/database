<?php

namespace Sentience\Database\Schemas;

use Sentience\Database\Databases\DatabaseInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Objects\Index;
use Sentience\Database\Queries\Objects\Join;
use Sentience\Database\Queries\Objects\Type;
use Sentience\Database\Queries\Objects\WhereGroup;
use Sentience\Database\Queries\Query;

class PgSQLSchema extends SQLSchema
{
    public function indexes(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $indexes = $database->select(['pg_catalog', 'pg_index'])
            ->columns([
                'index_name' => $database->select(['pg_catalog', 'pg_class'])
                    ->columns([['pg_catalog', 'pg_class', 'relname']])
                    ->where('pg_catalog.pg_class.oid = pg_catalog.pg_index.indexrelid'),
                'column_name' => ['pg_catalog', 'pg_attribute', 'attname'],
                'unique' => ['pg_catalog', 'pg_index', 'indisunique']
            ])
            ->innerJoin(
                ['pg_catalog', 'pg_attribute'],
                fn (Join $join): Join => $join
                    ->on(
                        ['pg_catalog', 'pg_attribute', 'attrelid'],
                        ['pg_catalog', 'pg_index', 'indrelid']
                    )
                    ->whereEquals(
                        ['pg_catalog', 'pg_attribute', 'attnum'],
                        Query::raw('ANY(pg_catalog.pg_index.indkey)')
                    )
            )
            ->whereEquals(
                Query::expressionf(
                    '(%s)',
                    $database->select(['pg_catalog', 'pg_class'])
                        ->columns([['pg_catalog', 'pg_class', 'relname']])
                        ->where(
                            'pg_catalog.pg_class.oid = pg_catalog.pg_index.indrelid'
                        )
                ),
                $table
            )
            ->whereEquals(
                Query::expressionf(
                    '(%s)',
                    $database->select(['pg_catalog', 'pg_namespace'])
                        ->columns([['pg_catalog', 'pg_namespace', 'nspname']])
                        ->whereEquals(
                            ['pg_catalog', 'pg_namespace', 'oid'],
                            $database->select(['pg_catalog', 'pg_class'])
                                ->columns([['pg_catalog', 'pg_class', 'relnamespace']])
                                ->where('pg_catalog.pg_class.oid = pg_catalog.pg_index.indrelid')
                        )
                ),
                Query::raw('current_schema()')
            )
            ->whereEquals(['pg_catalog', 'pg_index', 'indisprimary'], false)
            ->orderByAsc(
                Query::expressionf(
                    '(%s)',
                    $database->select(['pg_catalog', 'pg_class'])
                        ->columns([['pg_catalog', 'pg_class', 'relname']])
                        ->where('pg_catalog.pg_class.oid = pg_catalog.pg_index.indexrelid')
                )
            )
            ->orderByAsc(Query::raw('array_position(pg_catalog.pg_index.indkey::int2[], pg_catalog.pg_attribute.attnum)'))
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
            ->whereEquals('table_catalog', Query::raw('current_database()'))
            ->whereEquals('table_schema', Query::raw('current_schema()'));
    }

    protected function type(string $type, ?int $size): string|Type
    {
        return match ($type) {
            'DOUBLE PRECISION' => new Type(TypeEnum::Float, 64),
            'CHARACTER VARYING' => new Type(TypeEnum::String, $size ?? 255),
            'TIMESTAMP WITHOUT TIME ZONE',
            'TIMESTAMP WITH TIME ZONE' => new Type(TypeEnum::DateTime, $size ?? 0),
            default => parent::type($type, $size)
        };
    }

    protected function isIdentity(array $column): bool
    {
        $column = array_change_key_case($column, CASE_LOWER);

        if ((bool) preg_match('/nextval\(/i', (string) ($column['column_default'] ?? ''))) {
            return true;
        }

        return parent::isIdentity($column) && preg_match('/int|serial/i', $column['data_type']);
    }
}
