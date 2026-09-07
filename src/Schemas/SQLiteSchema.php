<?php

namespace Sentience\Database\Schemas;

use Sentience\Database\Databases\DatabaseInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Enums\ReferentialActionEnum;
use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Objects\Column;
use Sentience\Database\Queries\Objects\ForeignKeyConstraint;
use Sentience\Database\Queries\Objects\Index;
use Sentience\Database\Queries\Objects\Type;
use Sentience\Database\Queries\Objects\UniqueConstraint;

class SQLiteSchema extends SchemaAbstract
{
    public function tables(DatabaseInterface $database, DialectInterface $dialect): array
    {
        $tables = $database->select('sqlite_master')
            ->columns(['name'])
            ->whereEquals('type', 'table')
            ->execute()
            ->fetchAssocs();

        return array_column($tables, 'name');
    }

    public function columns(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $type = function (string $type): string|Type {
            preg_match('/^(\w+)(?:\((\d+)\))?$/', $type, $match);

            $size = !empty($match[2]) ? $match[2] : null;

            return match ($match[1]) {
                'BOOLEAN' => new Type(TypeEnum::Bool),
                'INTEGER' => new Type(TypeEnum::Int, 32),
                'BIGINT' => new Type(TypeEnum::Int, 64),
                'REAL' => new Type(TypeEnum::Float, 64),
                'VARCHAR',
                'TEXT' => new Type(TypeEnum::String, $size ?? PHP_INT_MAX),
                'DATETIME' => new Type(TypeEnum::DateTime, $size ?? 0),
                default => $type
            };
        };

        $columns = $database->query("PRAGMA table_info({$dialect->escapeIdentifier($table)})")->fetchAssocs();

        return array_map(
            fn (array $column): Column => new Column(
                $column['name'],
                $type(strtoupper($column['type'])),
                (bool) $column['notnull'],
                $column['dflt_value'],
                (bool) $column['pk'] && (bool) preg_match('/.*INT.*/i', $column['type'])
            ),
            $columns
        );
    }

    public function primaryKeys(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $rows = $database->query("PRAGMA table_info({$dialect->escapeIdentifier($table)})")->fetchAssocs();

        $columns = [];

        foreach ($rows as $row) {
            if (!(bool) $row['pk']) {
                continue;
            }

            $column = $row['name'];

            if (in_array($column, $columns)) {
                continue;
            }

            $columns[] = $column;
        }

        return $columns;
    }

    public function uniqueConstraints(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $uniqueIndexes = array_filter(
            $this->indexes($database, $dialect, $table),
            fn (Index $index) => $index->unique
        );

        return array_values(
            array_map(
                fn (Index $index) => new UniqueConstraint($index->columns, $index->name),
                $uniqueIndexes
            )
        );
    }

    public function foreignKeyConstraints(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $foreignKeys = $database->query("PRAGMA foreign_key_list({$dialect->escapeIdentifier($table)})")->fetchAssocs();

        return array_map(
            function (array $foreignKey): ForeignKeyConstraint {
                $column = $foreignKey['from'];
                $referenceTable = $foreignKey['table'];
                $referenceColumn = $foreignKey['to'];
                $onUpdate = $foreignKey['on_update'];
                $onDelete = $foreignKey['on_delete'];

                return new ForeignKeyConstraint(
                    $column,
                    $referenceTable,
                    $referenceColumn,
                    null,
                    ReferentialActionEnum::tryFrom($onUpdate) ?? $onUpdate,
                    ReferentialActionEnum::tryFrom($onDelete) ?? $onDelete
                );
            },
            $foreignKeys
        );
    }

    public function indexes(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $indexes = $database->query("PRAGMA index_list({$dialect->escapeIdentifier($table)})")->fetchAssocs();

        return array_map(
            function (array $index) use ($database): Index {
                $name = $index['name'];
                $columns = array_column($database->query("PRAGMA index_info({$name})")->fetchAssocs(), 'name');
                $unique = (bool) $index['unique'];

                return new Index($name, $columns, $unique);
            },
            $indexes
        );
    }
}
