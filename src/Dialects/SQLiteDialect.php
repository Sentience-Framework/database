<?php

namespace Sentience\Database\Dialects;

use Sentience\Database\Exceptions\QueryException;
use Sentience\Database\Queries\Enums\ConditionEnum;
use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Objects\AddForeignKeyConstraint;
use Sentience\Database\Queries\Objects\AddPrimaryKeys;
use Sentience\Database\Queries\Objects\AddUniqueConstraint;
use Sentience\Database\Queries\Objects\AlterColumn;
use Sentience\Database\Queries\Objects\Column;
use Sentience\Database\Queries\Objects\Condition;
use Sentience\Database\Queries\Objects\DropConstraint;
use Sentience\Database\Queries\Objects\ForeignKeyConstraint;
use Sentience\Database\Queries\Objects\OnConflict;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Queries\Objects\RenameColumn;
use Sentience\Database\Queries\Objects\UniqueConstraint;

class SQLiteDialect extends SQLDialect
{
    public const string DATETIME_FORMAT = 'Y-m-d H:i:s.u';
    public const bool GENERATED_BY_DEFAULT_AS_IDENTITY = false;

    public function createTable(
        bool $ifNotExists,
        string|array|Raw $table,
        array $columns,
        array $primaryKeys,
        array $constraints
    ): QueryWithParams {
        foreach ($columns as $column) {
            if (!$column->generatedByDefaultAsIdentity) {
                continue;
            }

            $primaryKeys = array_filter(
                $primaryKeys,
                fn (string $primaryKey): bool => $primaryKey != $column->name
            );
        }

        return parent::createTable(
            $ifNotExists,
            $table,
            $columns,
            $primaryKeys,
            $constraints
        );
    }

    protected function buildConditionLike(string &$query, array &$params, Condition $condition): void
    {
        [$value, $caseInsensitive] = $condition->value;

        $query .= sprintf(
            '%s %s %s',
            $this->escapeIdentifier($condition->identifier),
            $condition->condition == ConditionEnum::LIKE
            ? ($caseInsensitive ? ConditionEnum::LIKE->value : 'GLOB')
            : ($caseInsensitive ? ConditionEnum::NOT_LIKE->value : 'NOT GLOB'),
            $this->buildQuestionMarks(
                $params,
                $caseInsensitive ? $value : $this->likeToGlob($value)
            )
        );
    }

    protected function likeToGlob(string $likePattern): string
    {
        $globPattern = strtr(
            $likePattern,
            [
                '*' => '[*]',
                '?' => '[?]',
                '[' => '[[]',
                ']' => '[]]'
            ]
        );

        if (str_contains($globPattern, '\\')) {
            $globPattern = preg_replace_callback(
                '/\\\\(.)/us',
                fn (array $match): string => (string) match ($match[1]) {
                    '%' => '[%]',
                    '_' => '[_]',
                    '\\' => '[\\]',
                    default => $match[1]
                },
                $globPattern
            );
        }

        return strtr(
            $globPattern,
            [
                '%' => '*',
                '_' => '?'
            ]
        );
    }

    protected function buildOnConflict(string &$query, array &$params, ?OnConflict $onConflict, array $values, ?string $lastInsertId): void
    {
        if (is_string($onConflict?->conflict)) {
            throw new QueryException('SQLite does not support named constraints');
        }

        parent::buildOnConflict($query, $params, $onConflict, $values, $lastInsertId);
    }

    protected function buildColumn(Column $column): string
    {
        if ($column->generatedByDefaultAsIdentity) {
            return sprintf(
                '%s INTEGER PRIMARY KEY AUTOINCREMENT',
                $this->escapeIdentifier($column->name)
            );
        }

        return parent::buildColumn($column);
    }

    protected function buildUniqueConstraint(UniqueConstraint $uniqueConstraint): string
    {
        $uniqueConstraint->name = null;

        return parent::buildUniqueConstraint($uniqueConstraint);
    }

    protected function buildForeignKeyConstraint(ForeignKeyConstraint $foreignKeyConstraint): string
    {
        $foreignKeyConstraint->name = null;

        return parent::buildForeignKeyConstraint($foreignKeyConstraint);
    }

    protected function buildAlterTableAlterColumn(AlterColumn $alterColumn): string
    {
        throw new QueryException('SQLite does not support altering columns');
    }

    protected function buildAlterTableRenameColumn(RenameColumn $renameColumn): string
    {
        if ($this->version < 32500) {
            throw new QueryException('SQLite does not support renaming columns');
        }

        return parent::buildAlterTableRenameColumn($renameColumn);
    }

    protected function buildAlterTableAddPrimaryKeys(AddPrimaryKeys $addPrimaryKeys): string
    {
        throw new QueryException('SQLite does not support adding primary keys by altering the table');
    }

    protected function buildAlterTableAddUniqueConstraint(AddUniqueConstraint $addUniqueConstraint): string
    {
        throw new QueryException('SQLite does not support adding constraints by altering the table');
    }

    protected function buildAlterTableAddForeignKeyConstraint(AddForeignKeyConstraint $addForeignKeyConstraint): string
    {
        throw new QueryException('SQLite does not support adding constraints by altering the table');
    }

    protected function buildAlterTableDropConstraint(DropConstraint $dropConstraint): string
    {
        throw new QueryException('SQLite does not support dropping constraints by altering the table');
    }

    public function type(TypeEnum $type, ?int $size = null): string
    {
        return match ($type) {
            TypeEnum::FLOAT => 'REAL',
            default => parent::type($type, $size)
        };
    }

    public function onConflict(): bool
    {
        return $this->version >= 32400;
    }

    public function returning(): bool
    {
        return $this->version >= 33500;
    }
}
