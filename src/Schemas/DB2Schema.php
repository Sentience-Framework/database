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
use Sentience\Database\Queries\Query;
use Sentience\Database\Queries\SelectQuery;

class DB2Schema extends SchemaAbstract
{
    public function tables(DatabaseInterface $database, DialectInterface $dialect): array
    {
        $tables = $database->select(['SYSCAT', 'TABLES'])
            ->columns(['table_name' => 'TABNAME'])
            ->whereEquals('TABSCHEMA', Query::raw('CURRENT SCHEMA'))
            ->whereEquals('TYPE', 'T')
            ->orderByAsc('TABNAME')
            ->execute()
            ->fetchAssocs();

        return array_column($tables, 'table_name');
    }

    public function columns(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $columns = $database->select(['SYSCAT', 'COLUMNS'])
            ->columns([
                'column_name' => 'COLNAME',
                'data_type' => 'TYPENAME',
                'length' => 'LENGTH',
                'nulls' => 'NULLS',
                'column_default' => 'DEFAULT',
                'identity' => 'IDENTITY'
            ])
            ->whereEquals('TABSCHEMA', Query::raw('CURRENT SCHEMA'))
            ->whereEquals('TABNAME', $table)
            ->orderByAsc('COLNO')
            ->execute()
            ->fetchAssocs();

        return array_map(
            fn (array $column): Column => new Column(
                $column['column_name'],
                $this->type(
                    strtoupper((string) $column['data_type']),
                    !is_null($column['length']) ? (int) $column['length'] : null
                ),
                !$this->true($column['nulls']),
                $column['column_default'],
                $this->true($column['identity'])
            ),
            $columns
        );
    }

    public function primaryKeys(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $primaryKeys = $database->select(['SYSCAT', 'KEYCOLUSE'])
            ->columns(['column_name' => 'COLNAME'])
            ->whereEquals('TABSCHEMA', Query::raw('CURRENT SCHEMA'))
            ->whereEquals('TABNAME', $table)
            ->whereIn(
                'CONSTNAME',
                $this->constraints($database, $table, 'P')
            )
            ->orderByAsc('COLSEQ')
            ->execute()
            ->fetchAssocs();

        return array_column($primaryKeys, 'column_name');
    }

    public function uniqueConstraints(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $keys = $database->select(['SYSCAT', 'KEYCOLUSE'])
            ->columns([
                'constraint_name' => 'CONSTNAME',
                'column_name' => 'COLNAME'
            ])
            ->whereEquals('TABSCHEMA', Query::raw('CURRENT SCHEMA'))
            ->whereEquals('TABNAME', $table)
            ->whereIn(
                'CONSTNAME',
                $this->constraints($database, $table, 'U')
            )
            ->orderByAsc('CONSTNAME')
            ->orderByAsc('COLSEQ')
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
        $foreignKeys = $database->select(['SYSCAT', 'REFERENCES'])
            ->columns([
                'constraint_name' => ['SYSCAT', 'REFERENCES', 'CONSTNAME'],
                'column_name' => ['FK', 'COLNAME'],
                'reference_table' => ['SYSCAT', 'REFERENCES', 'REFTABNAME'],
                'reference_column' => ['PK', 'COLNAME'],
                'update_rule' => ['SYSCAT', 'REFERENCES', 'UPDATERULE'],
                'delete_rule' => ['SYSCAT', 'REFERENCES', 'DELETERULE']
            ])
            ->innerJoin(
                Query::alias(['SYSCAT', 'KEYCOLUSE'], 'FK'),
                fn (Join $join): Join => $join
                    ->on(
                        ['FK', 'CONSTNAME'],
                        ['SYSCAT', 'REFERENCES', 'CONSTNAME']
                    )
                    ->on(
                        ['FK', 'TABSCHEMA'],
                        ['SYSCAT', 'REFERENCES', 'TABSCHEMA']
                    )
                    ->on(
                        ['FK', 'TABNAME'],
                        ['SYSCAT', 'REFERENCES', 'TABNAME']
                    )
            )
            ->innerJoin(
                Query::alias(['SYSCAT', 'KEYCOLUSE'], 'PK'),
                fn (Join $join): Join => $join
                    ->on(
                        ['PK', 'CONSTNAME'],
                        ['SYSCAT', 'REFERENCES', 'REFKEYNAME']
                    )
                    ->on(
                        ['PK', 'TABSCHEMA'],
                        ['SYSCAT', 'REFERENCES', 'REFTABSCHEMA']
                    )
                    ->on(
                        ['PK', 'TABNAME'],
                        ['SYSCAT', 'REFERENCES', 'REFTABNAME']
                    )
                    ->on(
                        ['PK', 'COLSEQ'],
                        ['FK', 'COLSEQ']
                    )
            )
            ->whereEquals(['SYSCAT', 'REFERENCES', 'TABSCHEMA'], Query::raw('CURRENT SCHEMA'))
            ->whereEquals(['SYSCAT', 'REFERENCES', 'TABNAME'], $table)
            ->orderByAsc(['SYSCAT', 'REFERENCES', 'CONSTNAME'])
            ->orderByAsc(['FK', 'COLSEQ'])
            ->execute()
            ->fetchAssocs();

        return array_map(
            fn (array $foreignKey): ForeignKeyConstraint => new ForeignKeyConstraint(
                $foreignKey['column_name'],
                $foreignKey['reference_table'],
                $foreignKey['reference_column'],
                $foreignKey['constraint_name'],
                $this->referentialAction($foreignKey['update_rule']),
                $this->referentialAction($foreignKey['delete_rule'])
            ),
            $foreignKeys
        );
    }

    public function indexes(DatabaseInterface $database, DialectInterface $dialect, string $table): array
    {
        $indexes = $database->select(['SYSCAT', 'INDEXES'])
            ->columns([
                'index_name' => ['SYSCAT', 'INDEXES', 'INDNAME'],
                'column_name' => ['SYSCAT', 'INDEXCOLUSE', 'COLNAME'],
                'unique_rule' => ['SYSCAT', 'INDEXES', 'UNIQUERULE']
            ])
            ->innerJoin(
                ['SYSCAT', 'INDEXCOLUSE'],
                fn (Join $join): Join => $join
                    ->on(
                        ['SYSCAT', 'INDEXCOLUSE', 'INDSCHEMA'],
                        ['SYSCAT', 'INDEXES', 'INDSCHEMA']
                    )
                    ->on(
                        ['SYSCAT', 'INDEXCOLUSE', 'INDNAME'],
                        ['SYSCAT', 'INDEXES', 'INDNAME']
                    )
            )
            ->whereEquals(['SYSCAT', 'INDEXES', 'TABSCHEMA'], Query::raw('CURRENT SCHEMA'))
            ->whereEquals(['SYSCAT', 'INDEXES', 'TABNAME'], $table)
            ->whereNotEquals(['SYSCAT', 'INDEXES', 'UNIQUERULE'], 'P')
            ->orderByAsc(['SYSCAT', 'INDEXES', 'INDNAME'])
            ->orderByAsc(['SYSCAT', 'INDEXCOLUSE', 'COLSEQ'])
            ->execute()
            ->fetchAssocs();

        $indexNames = [];
        $indexColumns = [];
        $indexUnique = [];

        foreach ($indexes as $index) {
            $indexName = $index['index_name'];
            $columnName = $index['column_name'];
            $unique = trim((string) $index['unique_rule']) == 'U';

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
        return $database->select(['SYSCAT', 'TABCONST'])
            ->columns(['CONSTNAME'])
            ->whereEquals('TABSCHEMA', Query::raw('CURRENT SCHEMA'))
            ->whereEquals('TABNAME', $table)
            ->whereEquals('TYPE', $type);
    }

    protected function referentialAction(?string $rule): null|string|ReferentialActionEnum
    {
        $rule = strtoupper(trim((string) $rule));

        return match ($rule) {
            'A' => ReferentialActionEnum::NoAction,
            'C' => ReferentialActionEnum::Cascade,
            'N' => ReferentialActionEnum::SetNull,
            'R' => ReferentialActionEnum::Restrict,
            default => $rule
        };
    }

    protected function type(string $type, ?int $size): string|Type
    {
        return match ($type) {
            'SMALLINT' => new Type(TypeEnum::Bool),
            'DECFLOAT',
            'NUMERIC' => new Type(TypeEnum::Float, 64),
            'CHARACTER',
            'GRAPHIC',
            'VARGRAPHIC' => new Type(TypeEnum::String, $size ?? 255),
            'LONG VARCHAR',
            'CLOB',
            'DBCLOB' => new Type(TypeEnum::String, $size ?? PHP_INT_MAX),
            default => parent::type($type, $size)
        };
    }
}
