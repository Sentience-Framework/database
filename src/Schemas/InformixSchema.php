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

class InformixSchema extends SchemaAbstract
{
    public function tables(DatabaseInterface $database, DialectInterface $dialect): array
    {
        $tables = $database->select('systables')
            ->columns(['table_name' => 'tabname'])
            ->whereEquals('tabtype', 'T')
            ->whereGreaterThan('tabid', 99)
            ->orderByAsc('tabname')
            ->execute()
            ->fetchAssocs();

        return array_column($tables, 'table_name');
    }

    public function columns(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $columns = $database->select('syscolumns')
            ->columns([
                'column_name' => ['syscolumns', 'colname'],
                'column_type' => ['syscolumns', 'coltype'],
                'column_length' => ['syscolumns', 'collength'],
                'default_type' => ['sysdefaults', 'type'],
                'column_default' => ['sysdefaults', 'default']
            ])
            ->leftJoin(
                'sysdefaults',
                fn (Join $join): Join => $join
                    ->on(
                        ['sysdefaults', 'tabid'],
                        ['syscolumns', 'tabid']
                    )
                    ->on(
                        ['sysdefaults', 'colno'],
                        ['syscolumns', 'colno']
                    )
            )
            ->whereEquals(['syscolumns', 'tabid'], $this->tableId($database, $table))
            ->orderByAsc(['syscolumns', 'colno'])
            ->execute()
            ->fetchAssocs();

        return array_map(
            function (array $column): Column {
                $columnType = (int) $column['column_type'];
                $type = $this->columnType($columnType % 256);
                $length = (int) $column['column_length'];

                return new Column(
                    $column['column_name'],
                    $this->type($type, $this->size($type, $length)),
                    $columnType >= 256,
                    $this->columnDefault($type, $column['default_type'], $column['column_default']),
                    in_array($type, ['SERIAL', 'SERIAL8', 'BIGSERIAL'])
                );
            },
            $columns
        );
    }

    public function primaryKeys(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $indexes = $this->tableIndexes($database, $table);

        foreach ($this->constraints($database, $table, 'P') as $constraint) {
            return $indexes[$constraint['index_name']]['columns'] ?? [];
        }

        return [];
    }

    public function uniqueConstraints(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $indexes = $this->tableIndexes($database, $table);

        $uniqueConstraints = [];

        foreach ($this->constraints($database, $table, 'U') as $constraint) {
            $constraintName = $constraint['constraint_name'];
            $indexName = $constraint['index_name'];

            if (!array_key_exists($indexName, $indexes)) {
                continue;
            }

            $uniqueConstraints[] = new UniqueConstraint($indexes[$indexName]['columns'], $constraintName);
        }

        return $uniqueConstraints;
    }

    public function foreignKeyConstraints(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $references = $database->select('sysconstraints')
            ->columns([
                'constraint_name' => ['sysconstraints', 'constrname'],
                'index_name' => ['sysconstraints', 'idxname'],
                'reference_constraint_id' => ['sysreferences', 'primary'],
                'update_rule' => ['sysreferences', 'updrule'],
                'delete_rule' => ['sysreferences', 'delrule']
            ])
            ->innerJoin(
                'sysreferences',
                fn (Join $join): Join => $join->on(
                    ['sysreferences', 'constrid'],
                    ['sysconstraints', 'constrid']
                )
            )
            ->whereEquals(['sysconstraints', 'tabid'], $this->tableId($database, $table))
            ->whereEquals(['sysconstraints', 'constrtype'], 'R')
            ->orderByAsc(['sysconstraints', 'constrname'])
            ->execute()
            ->fetchAssocs();

        $referenceConstraints = $database->select('sysconstraints')
            ->columns([
                'constraint_id' => ['sysconstraints', 'constrid'],
                'index_name' => ['sysconstraints', 'idxname'],
                'table_name' => ['systables', 'tabname']
            ])
            ->innerJoin(
                'systables',
                fn (Join $join): Join => $join->on(
                    ['systables', 'tabid'],
                    ['sysconstraints', 'tabid']
                )
            )
            ->whereIn(
                ['sysconstraints', 'constrid'],
                array_column($references, 'reference_constraint_id')
            )
            ->execute()
            ->fetchAssocs();

        $indexes = $this->tableIndexes($database, $table);
        $referenceIndexes = [];

        $foreignKeyConstraints = [];

        foreach ($references as $reference) {
            $constraintName = trim((string) $reference['constraint_name']);
            $indexName = $reference['index_name'];
            $updateRule = $reference['update_rule'];
            $deleteRule = $reference['delete_rule'];

            $referenceConstraint = $this->referenceConstraint(
                $referenceConstraints,
                $reference['reference_constraint_id']
            );

            if (is_null($referenceConstraint)) {
                continue;
            }

            $referenceTable = trim((string) $referenceConstraint['table_name']);

            if (!array_key_exists($referenceTable, $referenceIndexes)) {
                $referenceIndexes[$referenceTable] = $this->tableIndexes($database, $referenceTable);
            }

            $columns = $indexes[$indexName]['columns'] ?? [];
            $referenceColumns = $referenceIndexes[$referenceTable][$referenceConstraint['index_name']]['columns'] ?? [];

            foreach ($columns as $index => $column) {
                if (!array_key_exists($index, $referenceColumns)) {
                    continue;
                }

                $foreignKeyConstraints[] = new ForeignKeyConstraint(
                    $column,
                    $referenceTable,
                    $referenceColumns[$index],
                    $constraintName,
                    $this->referentialAction($updateRule),
                    $this->referentialAction($deleteRule)
                );
            }
        }

        return $foreignKeyConstraints;
    }

    public function indexes(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $primaryKeyIndexes = array_column($this->constraints($database, $table, 'P'), 'index_name');

        $indexes = [];

        foreach ($this->tableIndexes($database, $table) as $name => $index) {
            if (in_array($name, $primaryKeyIndexes)) {
                continue;
            }

            $indexes[] = new Index($name, $index['columns'], $index['unique']);
        }

        return $indexes;
    }

    protected function referenceConstraint(array $constraints, mixed $constraintId): ?array
    {
        foreach ($constraints as $constraint) {
            if ($constraint['constraint_id'] == $constraintId) {
                return $constraint;
            }
        }

        return null;
    }

    protected function tableId(DatabaseInterface $database, string $table): SelectQuery
    {
        return $database->select('systables')
            ->columns(['tabid'])
            ->whereEquals('tabname', $table);
    }

    protected function constraints(DatabaseInterface $database, string $table, string $type): array
    {
        return $database->select('sysconstraints')
            ->columns([
                'constraint_name' => 'constrname',
                'index_name' => 'idxname'
            ])
            ->whereEquals('tabid', $this->tableId($database, $table))
            ->whereEquals('constrtype', $type)
            ->orderByAsc('constrname')
            ->execute()
            ->fetchAssocs();
    }

    protected function tableIndexes(DatabaseInterface $database, string $table): array
    {
        $columnNames = $this->columnNames($database, $table);
        $parts = $this->indexParts();

        $indexes = $database->select('sysindexes')
            ->columns([
                'index_name' => 'idxname',
                'index_type' => 'idxtype',
                ...$parts
            ])
            ->whereEquals('tabid', $this->tableId($database, $table))
            ->orderByAsc('idxname')
            ->execute()
            ->fetchAssocs();

        $tableIndexes = [];

        foreach ($indexes as $index) {
            $indexName = $index['index_name'];
            $unique = strtoupper(trim((string) $index['index_type'])) == 'U';

            $columns = [];

            foreach (array_keys($parts) as $part) {
                $columnNumber = abs((int) $index[$part]);

                if ($columnNumber == 0) {
                    continue;
                }

                if (!array_key_exists($columnNumber, $columnNames)) {
                    continue;
                }

                $columns[] = $columnNames[$columnNumber];
            }

            $tableIndexes[$indexName] = [
                'columns' => $columns,
                'unique' => $unique
            ];
        }

        return $tableIndexes;
    }

    protected function columnNames(DatabaseInterface $database, string $table): array
    {
        $columns = $database->select('syscolumns')
            ->columns([
                'column_number' => 'colno',
                'column_name' => 'colname'
            ])
            ->whereEquals('tabid', $this->tableId($database, $table))
            ->orderByAsc('colno')
            ->execute()
            ->fetchAssocs();

        return array_column($columns, 'column_name', 'column_number');
    }

    protected function indexParts(): array
    {
        $parts = [];

        for ($part = 1; $part <= 16; $part++) {
            $parts[sprintf('part%d', $part)] = sprintf('part%d', $part);
        }

        return $parts;
    }

    protected function columnType(int $type): string
    {
        return match ($type) {
            0 => 'CHAR',
            1 => 'SMALLINT',
            2 => 'INTEGER',
            3 => 'FLOAT',
            4 => 'SMALLFLOAT',
            5 => 'DECIMAL',
            6 => 'SERIAL',
            7 => 'DATE',
            8 => 'MONEY',
            10 => 'DATETIME',
            11 => 'BYTE',
            12 => 'TEXT',
            13 => 'VARCHAR',
            14 => 'INTERVAL',
            15 => 'NCHAR',
            16 => 'NVARCHAR',
            17 => 'INT8',
            18 => 'SERIAL8',
            40,
            43 => 'LVARCHAR',
            41 => 'CLOB',
            52 => 'BIGINT',
            53 => 'BIGSERIAL',
            default => (string) $type
        };
    }

    protected function referentialAction(?string $rule): null|string|ReferentialActionEnum
    {
        $rule = strtoupper(trim((string) $rule));

        return match ($rule) {
            'C' => ReferentialActionEnum::Cascade,
            'N' => ReferentialActionEnum::SetNull,
            'R' => ReferentialActionEnum::Restrict,
            default => $rule
        };
    }

    protected function size(string $type, int $length): ?int
    {
        return match ($type) {
            'DECIMAL',
            'MONEY' => intdiv($length, 256),
            'VARCHAR',
            'NVARCHAR' => $length % 256,
            'BYTE',
            'TEXT',
            'CLOB',
            'DATETIME',
            'INTERVAL' => null,
            default => $length
        };
    }

    protected function columnDefault(string $type, ?string $defaultType, ?string $default): ?string
    {
        return match (strtoupper(trim((string) $defaultType))) {
            'C' => 'CURRENT',
            'S' => 'DBSERVERNAME',
            'T' => 'TODAY',
            'U' => 'USER',
            'L' => $this->literal($type, $default),
            default => null
        };
    }

    protected function literal(string $type, ?string $default): ?string
    {
        if (is_null($default)) {
            return null;
        }

        $strings = [
            'CHAR',
            'NCHAR',
            'VARCHAR',
            'NVARCHAR',
            'LVARCHAR',
            'TEXT',
            'CLOB'
        ];

        if (in_array($type, $strings)) {
            return $default;
        }

        $position = strpos($default, ' ');

        return $position !== false
            ? trim(substr($default, $position + 1))
            : trim($default);
    }

    protected function type(string $type, ?int $size): string|Type
    {
        return match ($type) {
            'SMALLINT' => new Type(TypeEnum::Bool),
            'SMALLFLOAT' => new Type(TypeEnum::Float, 32),
            'MONEY' => new Type(TypeEnum::Float, 64),
            'SERIAL' => new Type(TypeEnum::Int, 32),
            'INT8',
            'SERIAL8',
            'BIGSERIAL' => new Type(TypeEnum::Int, 64),
            'CHAR',
            'NCHAR',
            'NVARCHAR',
            'LVARCHAR' => new Type(TypeEnum::String, $size ?? 255),
            'CLOB' => new Type(TypeEnum::String, $size ?? PHP_INT_MAX),
            'DATETIME' => new Type(TypeEnum::DateTime, $size ?? 0),
            default => parent::type($type, $size)
        };
    }
}
