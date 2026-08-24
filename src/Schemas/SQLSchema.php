<?php

namespace Sentience\Database\Schemas;

use Sentience\Database\DatabaseInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Enums\ReferentialActionEnum;
use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Objects\Column;
use Sentience\Database\Queries\Objects\ForeignKeyConstraint;
use Sentience\Database\Queries\Objects\Index;
use Sentience\Database\Queries\Objects\Type;
use Sentience\Database\Queries\Objects\UniqueConstraint;
use Sentience\Database\Queries\Objects\WhereGroup;
use Sentience\Database\Queries\Query;

class SQLSchema extends SchemaAbstract
{
    public function tables(DatabaseInterface $database, DialectInterface $dialect): array
    {
        $tables = $database->select(Query::raw('information_schema.tables'))
            ->columns(['table_name' => Query::raw('table_name')])
            ->whereLike(Query::raw('table_type'), 'BASE TABLE', true)
            ->whereGroup(fn (WhereGroup $whereGroup): WhereGroup => $this->databaseSchema($whereGroup))
            ->execute()
            ->fetchAssocs();

        return array_column($tables, 'table_name');
    }

    public function columns(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $columns = $database->select(Query::raw('information_schema.columns'))
            ->columns([
                'column_name' => Query::raw('column_name'),
                'data_type' => Query::raw('data_type'),
                'character_maximum_length' => Query::raw('character_maximum_length'),
                'numeric_precision' => Query::raw('numeric_precision'),
                'datetime_precision' => Query::raw('datetime_precision'),
                'is_nullable' => Query::raw('is_nullable'),
                'column_default' => Query::raw('column_default'),
                Query::raw('information_schema.columns.*')
            ])
            ->whereGroup(fn (WhereGroup $whereGroup): WhereGroup => $this->databaseSchema($whereGroup))
            ->whereEquals(Query::raw('table_name'), $table)
            ->orderByAsc(Query::raw('ordinal_position'))
            ->execute()
            ->fetchAssocs();

        return array_map(
            function (array $column): Column {
                $type = strtoupper($column['data_type']);
                $size = $column['character_maximum_length'] ?? $column['numeric_precision'] ?? $column['datetime_precision'];

                return new Column(
                    $column['column_name'],
                    $this->type($type, $size),
                    !$this->true($column['is_nullable']),
                    $column['column_default'],
                    $this->isIdentity($column)
                );
            },
            $columns
        );
    }

    public function primaryKeys(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $primaryKeys = $database->select(Query::raw('information_schema.key_column_usage'))
            ->columns(['column_name' => Query::raw('column_name')])
            ->whereGroup(fn (WhereGroup $whereGroup): WhereGroup => $this->databaseSchema($whereGroup))
            ->whereEquals(Query::raw('table_name'), $table)
            ->whereIn(
                Query::raw('constraint_name'),
                $database->select(Query::raw('information_schema.table_constraints'))
                    ->columns(['constraint_name' => Query::raw('constraint_name')])
                    ->whereGroup(fn (WhereGroup $whereGroup): WhereGroup => $this->databaseSchema($whereGroup))
                    ->whereEquals(Query::raw('table_name'), $table)
                    ->whereContains(Query::raw('constraint_type'), 'PRIMARY', true)
            )
            ->orderByAsc(Query::raw('ordinal_position'))
            ->execute()
            ->fetchAssocs();

        return array_column($primaryKeys, 'column_name');
    }

    public function uniqueConstraints(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $indexes = $database->select(Query::raw('information_schema.key_column_usage'))
            ->columns([
                'constraint_name' => Query::raw('constraint_name'),
                'column_name' => Query::raw('column_name')
            ])
            ->whereGroup(fn (WhereGroup $whereGroup): WhereGroup => $this->databaseSchema($whereGroup))
            ->whereEquals(Query::raw('table_name'), $table)
            ->whereIn(
                Query::raw('constraint_name'),
                $database->select(Query::raw('information_schema.table_constraints'))
                    ->columns(['constraint_name' => Query::raw('constraint_name')])
                    ->whereGroup(fn (WhereGroup $whereGroup): WhereGroup => $this->databaseSchema($whereGroup))
                    ->whereEquals(Query::raw('table_name'), $table)
                    ->whereContains(Query::raw('constraint_type'), 'UNIQUE', true)
            )
            ->orderByAsc(Query::raw('constraint_name'))
            ->orderByAsc(Query::raw('ordinal_position'))
            ->execute()
            ->fetchAssocs();

        $constraints = [];

        foreach ($indexes as $index) {
            $constraintName = $index['constraint_name'];
            $columnName = $index['column_name'];

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
        $constraints = $database->select(Query::raw('information_schema.referential_constraints'))
            ->columns([
                'constraint_name' => Query::raw('constraint_name'),
                'unique_constraint_name' => Query::raw('unique_constraint_name'),
                'update_rule' => Query::raw('update_rule'),
                'delete_rule' => Query::raw('delete_rule')
            ])
            ->whereIn(
                Query::raw('constraint_name'),
                $database->select(Query::raw('information_schema.table_constraints'))
                    ->columns(['constraint_name' => Query::raw('constraint_name')])
                    ->whereGroup(fn (WhereGroup $whereGroup): WhereGroup => $this->databaseSchema($whereGroup))
                    ->whereEquals(Query::raw('table_name'), $table)
                    ->whereContains(Query::raw('constraint_type'), 'FOREIGN KEY', true)
            )
            ->whereIsNotNull(Query::raw('unique_constraint_name'))
            ->execute()
            ->fetchAssocs();

        if (empty($constraints)) {
            return [];
        }

        $columns = $database->select(Query::raw('information_schema.key_column_usage'))
            ->columns([
                'constraint_name' => Query::raw('constraint_name'),
                'column_name' => Query::raw('column_name'),
                'position_in_unique_constraint' => Query::raw('position_in_unique_constraint')
            ])
            ->whereGroup(fn (WhereGroup $whereGroup): WhereGroup => $this->databaseSchema($whereGroup))
            ->whereEquals(Query::raw('table_name'), $table)
            ->whereIn(
                Query::raw('constraint_name'),
                array_column($constraints, 'constraint_name')
            )
            ->whereIsNotNull(Query::raw('position_in_unique_constraint'))
            ->orderByAsc(Query::raw('ordinal_position'))
            ->execute()
            ->fetchAssocs();

        $referenceColumns = $database->select(Query::raw('information_schema.key_column_usage'))
            ->columns([
                'constraint_name' => Query::raw('constraint_name'),
                'table_name' => Query::raw('table_name'),
                'column_name' => Query::raw('column_name'),
                'ordinal_position' => Query::raw('ordinal_position')
            ])
            ->whereGroup(fn (WhereGroup $whereGroup): WhereGroup => $this->databaseSchema($whereGroup))
            ->whereIn(
                Query::raw('constraint_name'),
                array_column($constraints, 'unique_constraint_name')
            )
            ->execute()
            ->fetchAssocs();

        $constraintColumns = [];

        foreach ($columns as $column) {
            $constraintName = $column['constraint_name'];

            $constraintColumns[$constraintName][] = $column;
        }

        $uniqueConstraintColumns = [];

        foreach ($referenceColumns as $referenceColumn) {
            $constraintName = $referenceColumn['constraint_name'];
            $tableName = $referenceColumn['table_name'];
            $columnName = $referenceColumn['column_name'];
            $ordinalPosition = (int) $referenceColumn['ordinal_position'];

            $uniqueConstraintColumns[$constraintName][$tableName][$ordinalPosition] = $columnName;
        }

        $foreignKeyConstraints = [];

        foreach ($constraints as $constraint) {
            $constraintName = $constraint['constraint_name'];
            $uniqueConstraintName = $constraint['unique_constraint_name'];
            $updateRule = $constraint['update_rule'];
            $deleteRule = $constraint['delete_rule'];

            $references = $uniqueConstraintColumns[$uniqueConstraintName];
            $referenceTable = array_key_first($references);
            $referenceTableColumns = $references[$referenceTable];

            $foreignKeyColumns = [];
            $foreignKeyReferenceColumns = [];

            foreach ($constraintColumns[$constraintName] as $column) {
                $columnName = $column['column_name'];
                $positionInUniqueConstraint = (int) $column['position_in_unique_constraint'];

                $foreignKeyColumns[] = $columnName;
                $foreignKeyReferenceColumns[] = $referenceTableColumns[$positionInUniqueConstraint];
            }

            $foreignKeyConstraints[] = new ForeignKeyConstraint(
                $foreignKeyColumns,
                $referenceTable,
                $foreignKeyReferenceColumns,
                $constraintName,
                ReferentialActionEnum::tryFrom(strtoupper($updateRule)) ?? $updateRule,
                ReferentialActionEnum::tryFrom(strtoupper($deleteRule)) ?? $deleteRule
            );
        }

        return $foreignKeyConstraints;
    }

    public function indexes(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $uniqueConstraints = $this->uniqueConstraints($database, $dialect, $table);

        return array_map(
            fn (UniqueConstraint $uniqueConstraint): Index => new Index(
                $uniqueConstraint->name,
                $uniqueConstraint->columns,
                true
            ),
            $uniqueConstraints
        );
    }

    protected function databaseSchema(WhereGroup $whereGroup): WhereGroup
    {
        return $whereGroup;
    }

    protected function type(string $type, ?int $size): string|Type
    {
        return match ($type) {
            'BOOLEAN' => new Type(TypeEnum::Bool),
            'INTEGER' => new Type(TypeEnum::Int, 32),
            'BIGINT' => new Type(TypeEnum::Int, 64),
            'REAL',
            'FLOAT',
            'DOUBLE',
            'DECIMAL' => new Type(TypeEnum::Float, 64),
            'VARCHAR',
            'TEXT' => new Type(TypeEnum::String, $size ?? PHP_INT_MAX),
            'DATETIME',
            'TIMESTAMP' => new Type(TypeEnum::DateTime, $size ?? 0),
            default => !is_null($size) ? sprintf('%s(%d)', $type, $size) : $size
        };
    }

    protected function isIdentity(array $column): bool
    {
        $column = array_change_key_case($column, CASE_LOWER);

        return $this->true($column['is_identity'] ?? null);
    }

    protected function true(mixed $value): bool
    {
        if (is_null($value)) {
            return false;
        }

        return in_array(
            strtoupper((string) $value),
            [
                'YES',
                'TRUE',
                '1'
            ]
        );
    }
}
