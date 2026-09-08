<?php

namespace Sentience\Database\Schemas;

use Sentience\Database\Databases\DatabaseInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Enums\ReferentialActionEnum;
use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Objects\Column;
use Sentience\Database\Queries\Objects\ForeignKeyConstraint;
use Sentience\Database\Queries\Objects\Index;
use Sentience\Database\Queries\Objects\Join;
use Sentience\Database\Queries\Objects\Type;
use Sentience\Database\Queries\Objects\UniqueConstraint;
use Sentience\Database\Queries\SelectQuery;

class OCISchema extends SchemaAbstract
{
    public function tables(DatabaseInterface $database, DialectInterface $dialect): array
    {
        $tables = $database->select('USER_TABLES')
            ->columns(['table_name' => 'TABLE_NAME'])
            ->orderByAsc('TABLE_NAME')
            ->execute()
            ->fetchAssocs();

        return array_column($tables, 'table_name');
    }

    public function columns(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $columns = $database->select('USER_TAB_COLUMNS')
            ->columns([
                'column_name' => 'COLUMN_NAME',
                'data_type' => 'DATA_TYPE',
                'data_length' => 'DATA_LENGTH',
                'char_length' => 'CHAR_LENGTH',
                'data_precision' => 'DATA_PRECISION',
                'data_scale' => 'DATA_SCALE',
                'nullable' => 'NULLABLE',
                'identity_column' => 'IDENTITY_COLUMN',
                'column_default' => 'DATA_DEFAULT'
            ])
            ->whereEquals('TABLE_NAME', $table)
            ->orderByAsc('COLUMN_ID')
            ->execute()
            ->fetchAssocs();

        return array_map(
            function (array $column): Column {
                $charLength = (int) $column['char_length'];
                $size = $charLength > 0 ? $charLength : (int) $column['data_length'];

                return new Column(
                    $column['column_name'],
                    $this->columnType(
                        strtoupper((string) $column['data_type']),
                        $size,
                        !is_null($column['data_precision']) ? (int) $column['data_precision'] : null,
                        !is_null($column['data_scale']) ? (int) $column['data_scale'] : null
                    ),
                    !$this->true($column['nullable']),
                    $this->columnDefault($column['column_default']),
                    $this->true($column['identity_column'])
                );
            },
            $columns
        );
    }

    public function primaryKeys(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $primaryKeys = $database->select('USER_CONS_COLUMNS')
            ->columns(['column_name' => 'COLUMN_NAME'])
            ->whereEquals('TABLE_NAME', $table)
            ->whereIn(
                'CONSTRAINT_NAME',
                $this->constraints($database, $table, 'P')
            )
            ->orderByAsc('POSITION')
            ->execute()
            ->fetchAssocs();

        return array_column($primaryKeys, 'column_name');
    }

    public function uniqueConstraints(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $keys = $database->select('USER_CONS_COLUMNS')
            ->columns([
                'constraint_name' => 'CONSTRAINT_NAME',
                'column_name' => 'COLUMN_NAME'
            ])
            ->whereEquals('TABLE_NAME', $table)
            ->whereIn(
                'CONSTRAINT_NAME',
                $this->constraints($database, $table, 'U')
            )
            ->orderByAsc('CONSTRAINT_NAME')
            ->orderByAsc('POSITION')
            ->execute()
            ->fetchAssocs();

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
        $constraints = $database->select('USER_CONSTRAINTS')
            ->columns([
                'constraint_name' => 'CONSTRAINT_NAME',
                'reference_constraint_name' => 'R_CONSTRAINT_NAME',
                'delete_rule' => 'DELETE_RULE'
            ])
            ->whereEquals('TABLE_NAME', $table)
            ->whereEquals('CONSTRAINT_TYPE', 'R')
            ->orderByAsc('CONSTRAINT_NAME')
            ->execute()
            ->fetchAssocs();

        $columns = $database->select('USER_CONS_COLUMNS')
            ->columns([
                'constraint_name' => 'CONSTRAINT_NAME',
                'table_name' => 'TABLE_NAME',
                'column_name' => 'COLUMN_NAME'
            ])
            ->whereIn(
                'CONSTRAINT_NAME',
                [
                    ...array_column($constraints, 'constraint_name'),
                    ...array_column($constraints, 'reference_constraint_name')
                ]
            )
            ->orderByAsc('POSITION')
            ->execute()
            ->fetchAssocs();

        $constraintColumns = [];
        $constraintTables = [];

        foreach ($columns as $column) {
            $constraintName = $column['constraint_name'];
            $tableName = $column['table_name'];
            $columnName = $column['column_name'];

            $constraintColumns[$constraintName][] = $columnName;
            $constraintTables[$constraintName] = $tableName;
        }

        $foreignKeyConstraints = [];

        foreach ($constraints as $constraint) {
            $constraintName = $constraint['constraint_name'];
            $referenceConstraintName = $constraint['reference_constraint_name'];
            $deleteRule = strtoupper((string) $constraint['delete_rule']);

            $referenceTable = $constraintTables[$referenceConstraintName] ?? null;
            $referenceColumns = $constraintColumns[$referenceConstraintName] ?? [];

            if (is_null($referenceTable)) {
                continue;
            }

            foreach ($constraintColumns[$constraintName] ?? [] as $index => $column) {
                if (!array_key_exists($index, $referenceColumns)) {
                    continue;
                }

                $foreignKeyConstraints[] = new ForeignKeyConstraint(
                    $column,
                    $referenceTable,
                    $referenceColumns[$index],
                    $constraintName,
                    null,
                    ReferentialActionEnum::tryFrom($deleteRule) ?? $deleteRule
                );
            }
        }

        return $foreignKeyConstraints;
    }

    public function indexes(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $indexes = $database->select('USER_INDEXES')
            ->columns([
                'index_name' => ['USER_INDEXES', 'INDEX_NAME'],
                'column_name' => ['USER_IND_COLUMNS', 'COLUMN_NAME'],
                'uniqueness' => ['USER_INDEXES', 'UNIQUENESS']
            ])
            ->innerJoin(
                'USER_IND_COLUMNS',
                fn (Join $join): Join => $join->on(
                    ['USER_IND_COLUMNS', 'INDEX_NAME'],
                    ['USER_INDEXES', 'INDEX_NAME']
                )
            )
            ->whereEquals(['USER_INDEXES', 'TABLE_NAME'], $table)
            ->whereNotIn(
                ['USER_INDEXES', 'INDEX_NAME'],
                $database->select('USER_CONSTRAINTS')
                    ->columns(['INDEX_NAME'])
                    ->whereEquals('TABLE_NAME', $table)
                    ->whereEquals('CONSTRAINT_TYPE', 'P')
                    ->whereIsNotNull('INDEX_NAME')
            )
            ->orderByAsc(['USER_INDEXES', 'INDEX_NAME'])
            ->orderByAsc(['USER_IND_COLUMNS', 'COLUMN_POSITION'])
            ->execute()
            ->fetchAssocs();

        $indexNames = [];
        $indexColumns = [];
        $indexUnique = [];

        foreach ($indexes as $index) {
            $indexName = $index['index_name'];
            $columnName = $index['column_name'];
            $unique = strtoupper((string) $index['uniqueness']) == 'UNIQUE';

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

    protected function constraints(DatabaseInterface $database, string $table, string $type): SelectQuery
    {
        return $database->select('USER_CONSTRAINTS')
            ->columns(['CONSTRAINT_NAME'])
            ->whereEquals('TABLE_NAME', $table)
            ->whereEquals('CONSTRAINT_TYPE', $type);
    }

    protected function columnType(string $type, ?int $size, ?int $precision, ?int $scale): string|Type
    {
        if ($type != 'NUMBER') {
            return $this->type($type, $size);
        }

        if (!is_null($scale) && $scale > 0) {
            return new Type(TypeEnum::Float, 64);
        }

        return match (true) {
            is_null($precision) => new Type(TypeEnum::Float, 64),
            $precision == 1 => new Type(TypeEnum::Bool),
            $precision > 10 => new Type(TypeEnum::Int, 64),
            default => new Type(TypeEnum::Int, 32)
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

        $default = trim((string) $default);

        if (strlen($default) == 0) {
            return null;
        }

        return strtoupper($default) != 'NULL' ? $default : null;
    }

    protected function type(string $type, ?int $size): string|Type
    {
        return match ($type) {
            'BINARY_FLOAT' => new Type(TypeEnum::Float, 32),
            'BINARY_DOUBLE' => new Type(TypeEnum::Float, 64),
            'CHAR',
            'NCHAR',
            'VARCHAR2',
            'NVARCHAR2' => new Type(TypeEnum::String, $size ?? 255),
            'CLOB',
            'NCLOB' => new Type(TypeEnum::String, $size ?? PHP_INT_MAX),
            'DATE' => new Type(TypeEnum::DateTime, 0),
            'TIMESTAMP' => new Type(TypeEnum::DateTime, $size ?? 0),
            default => parent::type($type, $size)
        };
    }
}
