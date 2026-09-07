<?php

namespace Sentience\Database\Schemas;

use Sentience\Database\Databases\DatabaseInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Enums\ReferentialActionEnum;
use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Interfaces\Sql;
use Sentience\Database\Queries\Objects\Column;
use Sentience\Database\Queries\Objects\ForeignKeyConstraint;
use Sentience\Database\Queries\Objects\Index;
use Sentience\Database\Queries\Objects\Join;
use Sentience\Database\Queries\Objects\Type;
use Sentience\Database\Queries\Objects\UniqueConstraint;
use Sentience\Database\Queries\Query;

class FirebirdSchema extends SchemaAbstract
{
    public function tables(DatabaseInterface $database, DialectInterface $dialect): array
    {
        $tables = $database->select('RDB$RELATIONS')
            ->columns(['table_name' => $this->trim(['RDB$RELATIONS', 'RDB$RELATION_NAME'])])
            ->whereEquals(['RDB$RELATIONS', 'RDB$RELATION_TYPE'], 0)
            ->whereEquals($this->coalesce(['RDB$RELATIONS', 'RDB$SYSTEM_FLAG']), 0)
            ->orderByAsc(['RDB$RELATIONS', 'RDB$RELATION_NAME'])
            ->execute()
            ->fetchAssocs();

        return array_column($tables, 'table_name');
    }

    public function columns(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $columns = $database->select(Query::alias('RDB$RELATION_FIELDS', 'RELATION_FIELDS'))
            ->columns([
                'column_name' => $this->trim(['RELATION_FIELDS', 'RDB$FIELD_NAME']),
                'field_type' => $this->trim(['TYPES', 'RDB$TYPE_NAME']),
                'field_sub_type' => ['FIELDS', 'RDB$FIELD_SUB_TYPE'],
                'field_scale' => ['FIELDS', 'RDB$FIELD_SCALE'],
                'field_precision' => ['FIELDS', 'RDB$FIELD_PRECISION'],
                'character_length' => ['FIELDS', 'RDB$CHARACTER_LENGTH'],
                'null_flag' => ['RELATION_FIELDS', 'RDB$NULL_FLAG'],
                'column_default' => ['RELATION_FIELDS', 'RDB$DEFAULT_SOURCE'],
                'identity_type' => ['RELATION_FIELDS', 'RDB$IDENTITY_TYPE']
            ])
            ->innerJoin(
                Query::alias('RDB$FIELDS', 'FIELDS'),
                fn (Join $join): Join => $join->on(
                    ['FIELDS', 'RDB$FIELD_NAME'],
                    ['RELATION_FIELDS', 'RDB$FIELD_SOURCE']
                )
            )
            ->leftJoin(
                Query::alias('RDB$TYPES', 'TYPES'),
                fn (Join $join): Join => $join
                    ->on(
                        ['TYPES', 'RDB$TYPE'],
                        ['FIELDS', 'RDB$FIELD_TYPE']
                    )
                    ->whereEquals(['TYPES', 'RDB$FIELD_NAME'], 'RDB$FIELD_TYPE')
            )
            ->whereEquals(['RELATION_FIELDS', 'RDB$RELATION_NAME'], $table)
            ->orderByAsc(['RELATION_FIELDS', 'RDB$FIELD_POSITION'])
            ->execute()
            ->fetchAssocs();

        return array_map(
            function (array $column): Column {
                $size = $column['character_length'] ?? $column['field_precision'];

                return new Column(
                    $column['column_name'],
                    $this->type(
                        $this->fieldType(
                            strtoupper((string) $column['field_type']),
                            !is_null($column['field_sub_type']) ? (int) $column['field_sub_type'] : null,
                            (int) $column['field_scale']
                        ),
                        !is_null($size) ? (int) $size : null
                    ),
                    (bool) $column['null_flag'],
                    $this->columnDefault($column['column_default']),
                    !is_null($column['identity_type'])
                );
            },
            $columns
        );
    }

    public function primaryKeys(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $primaryKeys = $this->constraintColumns($database, $table, 'PRIMARY KEY');

        return array_column($primaryKeys, 'column_name');
    }

    public function uniqueConstraints(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $keys = $this->constraintColumns($database, $table, 'UNIQUE');

        $constraints = [];

        foreach ($keys as $key) {
            $constraintName = $key['constraint_name'];
            $columnName = $key['column_name'];

            $constraints[$constraintName][] = $columnName;
        }

        $uniqueConstraints = [];

        foreach ($constraints as $name => $columns) {
            $uniqueConstraints[] = new UniqueConstraint($columns, $name);
        }

        return $uniqueConstraints;
    }

    public function foreignKeyConstraints(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $foreignKeys = $database->select(Query::alias('RDB$RELATION_CONSTRAINTS', 'FK'))
            ->columns([
                'constraint_name' => $this->trim(['FK', 'RDB$CONSTRAINT_NAME']),
                'column_name' => $this->trim(['FK_SEGMENTS', 'RDB$FIELD_NAME']),
                'reference_table' => $this->trim(['PK', 'RDB$RELATION_NAME']),
                'reference_column' => $this->trim(['PK_SEGMENTS', 'RDB$FIELD_NAME']),
                'update_rule' => $this->trim(['REFS', 'RDB$UPDATE_RULE']),
                'delete_rule' => $this->trim(['REFS', 'RDB$DELETE_RULE'])
            ])
            ->innerJoin(
                Query::alias('RDB$REF_CONSTRAINTS', 'REFS'),
                fn (Join $join): Join => $join->on(
                    ['REFS', 'RDB$CONSTRAINT_NAME'],
                    ['FK', 'RDB$CONSTRAINT_NAME']
                )
            )
            ->innerJoin(
                Query::alias('RDB$RELATION_CONSTRAINTS', 'PK'),
                fn (Join $join): Join => $join->on(
                    ['PK', 'RDB$CONSTRAINT_NAME'],
                    ['REFS', 'RDB$CONST_NAME_UQ']
                )
            )
            ->innerJoin(
                Query::alias('RDB$INDEX_SEGMENTS', 'FK_SEGMENTS'),
                fn (Join $join): Join => $join->on(
                    ['FK_SEGMENTS', 'RDB$INDEX_NAME'],
                    ['FK', 'RDB$INDEX_NAME']
                )
            )
            ->innerJoin(
                Query::alias('RDB$INDEX_SEGMENTS', 'PK_SEGMENTS'),
                fn (Join $join): Join => $join
                    ->on(
                        ['PK_SEGMENTS', 'RDB$INDEX_NAME'],
                        ['PK', 'RDB$INDEX_NAME']
                    )
                    ->on(
                        ['PK_SEGMENTS', 'RDB$FIELD_POSITION'],
                        ['FK_SEGMENTS', 'RDB$FIELD_POSITION']
                    )
            )
            ->whereEquals(['FK', 'RDB$RELATION_NAME'], $table)
            ->whereEquals(['FK', 'RDB$CONSTRAINT_TYPE'], 'FOREIGN KEY')
            ->orderByAsc(['FK', 'RDB$CONSTRAINT_NAME'])
            ->orderByAsc(['FK_SEGMENTS', 'RDB$FIELD_POSITION'])
            ->execute()
            ->fetchAssocs();

        return array_map(
            function (array $foreignKey): ForeignKeyConstraint {
                $updateRule = strtoupper((string) $foreignKey['update_rule']);
                $deleteRule = strtoupper((string) $foreignKey['delete_rule']);

                return new ForeignKeyConstraint(
                    $foreignKey['column_name'],
                    $foreignKey['reference_table'],
                    $foreignKey['reference_column'],
                    $foreignKey['constraint_name'],
                    ReferentialActionEnum::tryFrom($updateRule) ?? $updateRule,
                    ReferentialActionEnum::tryFrom($deleteRule) ?? $deleteRule
                );
            },
            $foreignKeys
        );
    }

    public function indexes(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $indexes = $database->select('RDB$INDICES')
            ->columns([
                'index_name' => $this->trim(['RDB$INDICES', 'RDB$INDEX_NAME']),
                'column_name' => $this->trim(['RDB$INDEX_SEGMENTS', 'RDB$FIELD_NAME']),
                'unique' => ['RDB$INDICES', 'RDB$UNIQUE_FLAG']
            ])
            ->innerJoin(
                'RDB$INDEX_SEGMENTS',
                fn (Join $join): Join => $join->on(
                    ['RDB$INDEX_SEGMENTS', 'RDB$INDEX_NAME'],
                    ['RDB$INDICES', 'RDB$INDEX_NAME']
                )
            )
            ->whereEquals(['RDB$INDICES', 'RDB$RELATION_NAME'], $table)
            ->whereNotIn(
                ['RDB$INDICES', 'RDB$INDEX_NAME'],
                $database->select('RDB$RELATION_CONSTRAINTS')
                    ->columns([['RDB$RELATION_CONSTRAINTS', 'RDB$INDEX_NAME']])
                    ->whereEquals(['RDB$RELATION_CONSTRAINTS', 'RDB$RELATION_NAME'], $table)
                    ->whereEquals(['RDB$RELATION_CONSTRAINTS', 'RDB$CONSTRAINT_TYPE'], 'PRIMARY KEY')
                    ->whereIsNotNull(['RDB$RELATION_CONSTRAINTS', 'RDB$INDEX_NAME'])
            )
            ->orderByAsc(['RDB$INDICES', 'RDB$INDEX_NAME'])
            ->orderByAsc(['RDB$INDEX_SEGMENTS', 'RDB$FIELD_POSITION'])
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

    protected function constraintColumns(DatabaseInterface $database, string $table, string $type): array
    {
        return $database->select('RDB$RELATION_CONSTRAINTS')
            ->columns([
                'constraint_name' => $this->trim(['RDB$RELATION_CONSTRAINTS', 'RDB$CONSTRAINT_NAME']),
                'column_name' => $this->trim(['RDB$INDEX_SEGMENTS', 'RDB$FIELD_NAME'])
            ])
            ->innerJoin(
                'RDB$INDEX_SEGMENTS',
                fn (Join $join): Join => $join->on(
                    ['RDB$INDEX_SEGMENTS', 'RDB$INDEX_NAME'],
                    ['RDB$RELATION_CONSTRAINTS', 'RDB$INDEX_NAME']
                )
            )
            ->whereEquals(['RDB$RELATION_CONSTRAINTS', 'RDB$RELATION_NAME'], $table)
            ->whereEquals(['RDB$RELATION_CONSTRAINTS', 'RDB$CONSTRAINT_TYPE'], $type)
            ->orderByAsc(['RDB$RELATION_CONSTRAINTS', 'RDB$CONSTRAINT_NAME'])
            ->orderByAsc(['RDB$INDEX_SEGMENTS', 'RDB$FIELD_POSITION'])
            ->execute()
            ->fetchAssocs();
    }

    protected function trim(array $identifier): Sql
    {
        return Query::expressionf('TRIM(%s)', Query::identifier($identifier));
    }

    protected function coalesce(array $identifier): Sql
    {
        return Query::expressionf('COALESCE(%s, 0)', Query::identifier($identifier));
    }

    protected function fieldType(string $type, ?int $subType, int $scale): string
    {
        if ($type == 'BLOB') {
            return $type;
        }

        if ($scale < 0) {
            return 'DECIMAL';
        }

        return match ($subType) {
            1,
            2 => 'DECIMAL',
            default => $type
        };
    }

    protected function columnDefault(mixed $default): ?string
    {
        if (is_resource($default)) {
            $default = stream_get_contents($default);
        }

        if (is_null($default)) {
            return null;
        }

        $default = trim(
            (string) preg_replace('/^\s*DEFAULT\s+/i', '', (string) $default)
        );

        return strlen($default) > 0 ? $default : null;
    }

    protected function type(string $type, ?int $size): string|Type
    {
        return match ($type) {
            'SHORT',
            'LONG' => new Type(TypeEnum::Int, 32),
            'INT64',
            'INT128' => new Type(TypeEnum::Int, 64),
            'DOUBLE',
            'DECFLOAT16',
            'DECFLOAT34',
            'NUMERIC' => new Type(TypeEnum::Float, 64),
            'TEXT',
            'VARYING',
            'CSTRING' => new Type(TypeEnum::String, $size ?? 255),
            'BLOB' => new Type(TypeEnum::String, $size ?? PHP_INT_MAX),
            'TIMESTAMP_TZ' => new Type(TypeEnum::DateTime, $size ?? 0),
            default => parent::type($type, $size)
        };
    }
}
