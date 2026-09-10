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
                'relanem' => ['index_class', 'relname'],
                'attname' => ['pg_catalog', 'pg_attribute', 'attname'],
                'indisunique' => ['pg_catalog', 'pg_index', 'indisunique']
            ])
            ->innerJoinTable(
                ['pg_catalog', 'pg_class'],
                fn (Join $join): Join => $join->on(
                    ['index_class', 'oid'],
                    ['pg_catalog', 'pg_index', 'indexrelid']
                ),
                'index_class'
            )
            ->innerJoinTable(
                ['pg_catalog', 'pg_class'],
                fn (Join $join): Join => $join->on(
                    ['table_class', 'oid'],
                    ['pg_catalog', 'pg_index', 'indrelid']
                ),
                'table_class'
            )
            ->innerJoin(
                ['pg_catalog', 'pg_namespace'],
                fn (Join $join): Join => $join->on(
                    ['pg_catalog', 'pg_namespace', 'oid'],
                    ['table_class', 'relnamespace']
                )
            )
            ->innerJoin(
                ['pg_catalog', 'pg_attribute'],
                fn (Join $join): Join => $join
                    ->on(
                        ['pg_catalog', 'pg_attribute', 'attrelid'],
                        ['pg_catalog', 'pg_index', 'indrelid']
                    )
                    ->whereEquals(
                        ['pg_catalog', 'pg_attribute', 'attnum'],
                        Query::raw('ANY (pg_catalog.pg_index.indkey)')
                    )
            )
            ->whereEquals(['table_class', 'relname'], $table)
            ->whereEquals(
                ['pg_catalog', 'pg_namespace', 'nspname'],
                Query::raw('current_schema()')
            )
            ->whereEquals(['pg_catalog', 'pg_index', 'indisprimary'], false)
            ->orderByAsc(['index_class', 'relname'])
            ->orderByAsc(Query::raw('array_position(pg_catalog.pg_index.indkey::int8[], pg_catalog.pg_attribute.attnum)'))
            ->execute()
            ->fetchAssocs();

        $indexNames = [];
        $indexColumns = [];
        $indexUnique = [];

        foreach ($indexes as $index) {
            $relanem = $index['relanem'];
            $attname = $index['attname'];
            $indisunique = (bool) $index['indisunique'];

            if (!in_array($relanem, $indexNames)) {
                $indexNames[] = $relanem;
            }

            if (!array_key_exists($relanem, $indexColumns)) {
                $indexColumns[$relanem] = [];
            }

            $indexColumns[$relanem][] = $attname;
            $indexUnique[$relanem] = $indisunique;
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
