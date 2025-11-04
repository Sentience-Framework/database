<?php

namespace Sentience\Database\Dialects;

use BackedEnum;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Throwable;
use Sentience\Database\Exceptions\QueryException;
use Sentience\Database\Queries\Enums\ConditionEnum;
use Sentience\Database\Queries\Objects\AddColumn;
use Sentience\Database\Queries\Objects\AddForeignKeyConstraint;
use Sentience\Database\Queries\Objects\AddPrimaryKeys;
use Sentience\Database\Queries\Objects\AddUniqueConstraint;
use Sentience\Database\Queries\Objects\Alias;
use Sentience\Database\Queries\Objects\AlterColumn;
use Sentience\Database\Queries\Objects\Column;
use Sentience\Database\Queries\Objects\Condition;
use Sentience\Database\Queries\Objects\ConditionGroup;
use Sentience\Database\Queries\Objects\DropColumn;
use Sentience\Database\Queries\Objects\DropConstraint;
use Sentience\Database\Queries\Objects\ForeignKeyConstraint;
use Sentience\Database\Queries\Objects\Having;
use Sentience\Database\Queries\Objects\OnConflict;
use Sentience\Database\Queries\Objects\OrderBy;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Queries\Objects\RenameColumn;
use Sentience\Database\Queries\Objects\UniqueConstraint;
use Sentience\Database\Queries\Query;

class SQLDialect extends DialectAbstract
{
    protected const string DATETIME_FORMAT = 'Y-m-d H:i:s';
    protected const bool ESCAPE_ANSI = true;
    protected const string ESCAPE_IDENTIFIER = '"';
    protected const string ESCAPE_STRING = "'";
    protected const bool ON_CONFLICT = false;
    protected const bool RETURNING = false;

    public function select(
        bool $distinct,
        array $columns,
        string|array|Alias|Raw $table,
        array $joins,
        array $where,
        array $groupBy,
        ?Having $having,
        array $orderBy,
        ?int $limit,
        ?int $offset
    ): QueryWithParams {
        $query = 'SELECT';
        $params = [];

        if ($distinct) {
            $query .= ' DISTINCT';
        }

        $query .= ' ';
        $query .= count($columns) > 0
            ? implode(
                ', ',
                array_map(
                    function (string|array|Alias|Raw $column): string {
                        if ($column instanceof Alias) {
                            return $this->escapeIdentifierWithAlias($column->identifier, $column->alias);
                        }

                        if ($column instanceof Raw) {
                            return (string) $column;
                        }

                        return $this->escapeIdentifier($column);
                    },
                    $columns
                )
            )
            : '*';

        $query .= ' FROM';

        $this->buildTable($query, $table);
        $this->buildJoins($query, $joins);
        $this->buildWhere($query, $params, $where);
        $this->buildGroupBy($query, $groupBy);
        $this->buildHaving($query, $params, $having);
        $this->buildOrderBy($query, $orderBy);
        $this->buildLimit($query, $limit, $offset);
        $this->buildOffset($query, $limit, $offset);

        $query .= ';';

        return new QueryWithParams($query, $params);
    }

    public function insert(
        string|array|Alias|Raw $table,
        array $values,
        ?OnConflict $onConflict,
        ?array $returning,
        ?string $lastInsertId
    ): QueryWithParams {
        if (count($values) == 0) {
            throw new QueryException('no insert values specified');
        }

        $query = 'INSERT INTO';
        $params = [];

        $this->buildTable($query, $table);

        $query .= sprintf(
            ' (%s)',
            implode(
                ', ',
                array_map(
                    function (string|array|Alias|Raw $column): string {
                        if ($column instanceof Alias) {
                            return $this->escapeIdentifier($column->identifier);
                        }

                        if ($column instanceof Raw) {
                            return (string) $column;
                        }

                        return $this->escapeIdentifier($column);
                    },
                    array_keys($values)
                )
            )
        );

        $query .= sprintf(
            ' VALUES (%s)',
            implode(
                ', ',
                array_map(
                    function (mixed $value) use (&$params): string {
                        if ($value instanceof Raw) {
                            return (string) $value;
                        }

                        $params[] = $value;

                        return '?';
                    },
                    $values
                )
            )
        );

        $this->buildOnConflict($query, $params, $onConflict, $values, $lastInsertId);
        $this->buildReturning($query, $returning);

        $query .= ';';

        return new QueryWithParams($query, $params);
    }

    public function update(
        string|array|Alias|Raw $table,
        array $values,
        array $where,
        ?array $returning
    ): QueryWithParams {
        if (count($values) == 0) {
            throw new QueryException('no update values specified');
        }

        $query = 'UPDATE';
        $params = [];

        $this->buildTable($query, $table);

        $query .= ' SET ';
        $query .= implode(
            ', ',
            array_map(
                function (mixed $value, string $key) use (&$params): string {
                    if ($value instanceof Raw) {
                        return sprintf(
                            '%s = %s',
                            $this->escapeIdentifier($key),
                            (string) $value
                        );
                    }

                    $params[] = $value;

                    return sprintf('%s = ?', $this->escapeIdentifier($key));
                },
                $values,
                array_keys($values)
            )
        );

        $this->buildWhere($query, $params, $where);
        $this->buildReturning($query, $returning);

        $query .= ';';

        return new QueryWithParams($query, $params);
    }

    public function delete(
        string|array|Alias|Raw $table,
        array $where,
        ?array $returning
    ): QueryWithParams {
        $query = 'DELETE FROM';
        $params = [];

        $this->buildTable($query, $table);
        $this->buildWhere($query, $params, $where);
        $this->buildReturning($query, $returning);

        $query .= ';';

        return new QueryWithParams($query, $params);
    }

    public function createTable(
        bool $ifNotExists,
        string|array|Alias|Raw $table,
        array $columns,
        array $primaryKeys,
        array $constraints
    ): QueryWithParams {
        if (count($columns) == 0) {
            throw new QueryException('no table columns specified');
        }

        if (count($primaryKeys) == 0) {
            throw new QueryException('no table primary key(s) specified');
        }

        $query = 'CREATE TABLE';

        if ($ifNotExists) {
            $query .= ' IF NOT EXISTS';
        }

        $this->buildTable($query, $table);

        $query .= ' (';

        foreach ($columns as $index => $column) {
            if ($index > 0) {
                $query .= ', ';
            }

            $query .= $this->buildColumn($column);
        }

        $query .= sprintf(
            ', PRIMARY KEY (%s)',
            implode(
                ', ',
                array_map(
                    fn (string|Raw $column): string => $this->escapeIdentifier($column),
                    $primaryKeys
                )
            )
        );

        foreach ($constraints as $constraint) {
            $query .= ', ';
            $query .= match (true) {
                $constraint instanceof UniqueConstraint => $this->buildUniqueConstraint($constraint),
                $constraint instanceof ForeignKeyConstraint => $this->buildForeignKeyConstraint($constraint),
                default => (string) $constraint
            };
        }

        $query .= ');';

        return new QueryWithParams($query);
    }

    public function alterTable(
        string|array|Alias|Raw $table,
        array $alters
    ): array {
        if (count($alters) == 0) {
            throw new QueryException('no table alters specified');
        }

        return array_map(
            function (object $alter) use ($table): QueryWithParams {
                $query = 'ALTER TABLE';

                $this->buildTable($query, $table);

                $query .= ' ';
                $query .= match (true) {
                    $alter instanceof AddColumn => $this->buildAlterTableAddColumn($alter),
                    $alter instanceof AlterColumn => $this->buildAlterTableAlterColumn($alter),
                    $alter instanceof RenameColumn => $this->buildAlterTableRenameColumn($alter),
                    $alter instanceof DropColumn => $this->buildAlterTableDropColumn($alter),
                    $alter instanceof AddPrimaryKeys => $this->buildAlterTableAddPrimaryKeys($alter),
                    $alter instanceof AddUniqueConstraint => $this->buildAlterTableAddUniqueConstraint($alter),
                    $alter instanceof AddForeignKeyConstraint => $this->buildAlterTableAddForeignKeyConstraint($alter),
                    $alter instanceof DropConstraint => $this->buildAlterTableDropConstraint($alter),
                    default => (string) $alter
                };

                $query .= ';';

                return new QueryWithParams($query);
            },
            $alters
        );
    }

    public function dropTable(
        bool $ifExists,
        string|array|Alias|Raw $table
    ): QueryWithParams {
        $query = 'DROP TABLE';

        if ($ifExists) {
            $query .= ' IF EXISTS';
        }

        $this->buildTable($query, $table);

        $query .= ';';

        return new QueryWithParams($query);
    }

    protected function buildTable(string &$query, string|array|Alias|Raw $table): void
    {
        $query .= ' ';

        if ($table instanceof Alias) {
            $query .= $this->escapeIdentifierWithAlias($table->identifier, $table->alias);

            return;
        }

        if ($table instanceof Raw) {
            $query .= (string) $table;

            return;
        }

        $query .= $this->escapeIdentifier($table);
    }

    protected function buildJoins(string &$query, array $joins): void
    {
        if (count($joins) == 0) {
            return;
        }

        $query .= ' ';

        foreach ($joins as $index => $join) {
            if ($index > 0) {
                $query .= ' ';
            }

            if ($join instanceof Raw) {
                $query .= (string) $join;

                continue;
            }

            $joinTableHasAlias = $join->joinTable instanceof Alias;
            $joinTable = $joinTableHasAlias ? $join->joinTable->identifier : $join->joinTable;
            $joinTableAlias = $joinTableHasAlias ? $join->joinTable->alias : null;
            $joinTableColumn = $join->joinTableColumn;
            $onTable = $join->onTable;
            $onTableColumn = $join->onTableColumn;

            $query .= sprintf(
                '%s %s ON %s.%s = %s.%s',
                $join->join->value,
                $this->escapeIdentifierWithAlias($joinTable, $joinTableAlias),
                $this->escapeIdentifier($joinTableAlias ?? $joinTable),
                $this->escapeIdentifier($joinTableColumn),
                $this->escapeIdentifier($onTable),
                $this->escapeIdentifier($onTableColumn)
            );
        }
    }

    protected function buildWhere(string &$query, array &$params, array $where): void
    {
        if (count($where) == 0) {
            return;
        }

        $query .= ' WHERE ';

        foreach ($where as $index => $condition) {
            $condition instanceof Condition
                ? $this->buildCondition($query, $params, $index, $condition)
                : $this->buildConditionGroup($query, $params, $index, $condition);
        }
    }

    protected function buildCondition(string &$query, array &$params, int $index, Condition $condition): void
    {
        if ($index > 0) {
            $query .= sprintf(' %s ', $condition->chain->value);
        }

        match ($condition->condition) {
            ConditionEnum::BETWEEN,
            ConditionEnum::NOT_BETWEEN => $this->buildConditionBetween($query, $params, $condition),
            ConditionEnum::LIKE,
            ConditionEnum::NOT_LIKE => $this->buildConditionLike($query, $params, $condition),
            ConditionEnum::IN,
            ConditionEnum::NOT_IN => $this->buildConditionIn($query, $params, $condition),
            ConditionEnum::REGEX,
            ConditionEnum::NOT_REGEX => $this->buildConditionRegex($query, $params, $condition),
            ConditionEnum::RAW => $this->buildConditionRaw($query, $params, $condition),
            default => $this->buildConditionOperator($query, $params, $condition)
        };
    }

    protected function buildConditionOperator(string &$query, array &$params, Condition $condition): void
    {
        if (is_null($condition->value)) {
            $query .= sprintf(
                '%s %s',
                $this->escapeIdentifier($condition->identifier),
                $condition->condition == ConditionEnum::EQUALS ? 'IS NULL' : 'IS NOT NULL'
            );

            return;
        }

        $query .= sprintf(
            '%s %s ?',
            $this->escapeIdentifier($condition->identifier),
            $condition->condition->value
        );

        array_push($params, $condition->value);
    }

    protected function buildConditionBetween(string &$query, array &$params, Condition $condition): void
    {
        $query .= sprintf(
            '%s %s ? AND ?',
            $this->escapeIdentifier($condition->identifier),
            $condition->condition->value
        );

        array_push($params, ...$condition->value);

        return;
    }

    protected function buildConditionLike(string &$query, array &$params, Condition $condition): void
    {
        $query .= sprintf(
            '%s %s ?',
            $this->escapeIdentifier($condition->identifier),
            $condition->condition->value
        );

        array_push($params, $condition->value);

        return;
    }

    protected function buildConditionIn(string &$query, array &$params, Condition $condition): void
    {
        if (count($condition->value) == 0) {
            $query .= $condition->condition == ConditionEnum::IN ? '1 <> 1' : '1 = 1';

            return;
        }

        $query .= sprintf(
            '%s %s (%s)',
            $this->escapeIdentifier($condition->identifier),
            $condition->condition->value,
            implode(', ', array_fill(0, count($condition->value), '?'))
        );

        array_push($params, ...$condition->value);

        return;
    }

    protected function buildConditionRegex(string &$query, array &$params, Condition $condition): void
    {
        if ($condition->condition == ConditionEnum::NOT_REGEX) {
            $query .= 'NOT ';
        }

        $query .= sprintf(
            'REGEXP_LIKE(%s, ?, ?)',
            $this->escapeIdentifier($condition->identifier)
        );

        array_push($params, ...$condition->value);

        return;
    }

    protected function buildConditionRegexOperator(string &$query, array &$params, Condition $condition, string $equals, string $notEquals): void
    {
        $query .= sprintf(
            '%s %s ?',
            $this->escapeIdentifier($condition->identifier),
            $condition->condition == ConditionEnum::REGEX ? $equals : $notEquals
        );

        [$pattern, $flags] = $condition->value;

        array_push(
            $params,
            !empty($flags)
            ? sprintf(
                '(?%s)%s',
                $flags,
                $pattern
            ) : $pattern
        );
    }

    protected function buildConditionRaw(string &$query, array &$params, Condition $condition): void
    {
        $query .= sprintf('(%s)', $condition->identifier);

        array_push($params, ...$condition->value);

        return;
    }

    protected function buildConditionGroup(string &$query, array &$params, int $index, ConditionGroup $group): void
    {
        if ($index > 0) {
            $query .= sprintf(' %s ', $group->chain->value);
        }

        $conditions = $group->getConditions();

        $query .= '(';

        foreach ($conditions as $index => $condition) {
            $condition instanceof Condition
                ? $this->buildCondition($query, $params, $index, $condition)
                : $this->buildConditionGroup($query, $params, $index, $condition);
        }

        $query .= ')';
    }

    protected function buildGroupBy(string &$query, array $groupBy): void
    {
        if (count($groupBy) == 0) {
            return;
        }

        $query .= sprintf(
            ' GROUP BY %s',
            implode(
                ', ',
                array_map(
                    fn (string|array|Raw $column): string => $this->escapeIdentifier($column),
                    $groupBy
                )
            )
        );
    }

    protected function buildHaving(string &$query, array &$params, ?Having $having): void
    {
        if (is_null($having)) {
            return;
        }

        $query .= ' HAVING ' . $having->query;

        array_push($params, ...$having->params);
    }

    protected function buildOrderBy(string &$query, array $orderBy): void
    {
        if (count($orderBy) == 0) {
            return;
        }

        $query .= sprintf(
            ' ORDER BY %s',
            implode(
                ', ',
                array_map(
                    fn (OrderBy $orderBy): string => sprintf(
                        '%s %s',
                        $this->escapeIdentifier($orderBy->column),
                        $orderBy->direction->value
                    ),
                    $orderBy
                )
            )
        );
    }

    protected function buildLimit(string &$query, ?int $limit, ?int $offset): void
    {
        if (is_null($limit)) {
            return;
        }

        $query .= ' LIMIT ' . $limit;
    }

    protected function buildOffset(string &$query, ?int $limit, ?int $offset): void
    {
        if (is_null($limit)) {
            return;
        }

        if (is_null($offset)) {
            return;
        }

        $query .= ' OFFSET ' . $offset;
    }

    protected function buildOnConflict(string &$query, array &$params, ?OnConflict $onConflict, array $values, ?string $lastInsertId): void
    {
        return;
    }

    protected function buildReturning(string &$query, ?array $returning): void
    {
        if (!$this->returning()) {
            return;
        }

        if (is_null($returning)) {
            return;
        }

        $columns = count($returning) > 0
            ? implode(
                ', ',
                array_map(
                    fn (string $column): string => $this->escapeIdentifier($column),
                    $returning
                )
            )
            : '*';

        $query .= ' RETURNING ' . $columns;
    }

    protected function buildColumn(Column $column): string
    {
        $sql = sprintf(
            '%s %s',
            $this->escapeIdentifier($column->name),
            $column->type
        );

        if ($column->notNull) {
            $sql .= ' NOT NULL';
        }

        if (!is_null($column->default)) {
            $default = !($column->default instanceof Raw)
                ? $this->castToQuery($column->default)
                : (string) $column->default;

            $sql .= ' DEFAULT ' . $default;
        }

        foreach ($column->options as $option) {
            $sql .= ' ';
            $sql .= (string) $option;
        }

        return $sql;
    }

    protected function buildUniqueConstraint(UniqueConstraint $uniqueConstraint): string
    {
        $sql = sprintf(
            'UNIQUE (%s)',
            implode(
                ', ',
                array_map(
                    fn (string $column): string => $this->escapeIdentifier($column),
                    $uniqueConstraint->columns
                )
            )
        );

        if ($uniqueConstraint->name) {
            return sprintf(
                'CONSTRAINT %s %s',
                $this->escapeIdentifier($uniqueConstraint->name),
                $sql
            );
        }

        return $sql;
    }

    protected function buildForeignKeyConstraint(ForeignKeyConstraint $foreignKeyConstraint): string
    {
        $sql = sprintf(
            'FOREIGN KEY (%s) REFERENCES %s (%s)',
            $foreignKeyConstraint->column,
            $foreignKeyConstraint->referenceTable,
            $foreignKeyConstraint->referenceColumn
        );

        if ($foreignKeyConstraint->name) {
            $sql = sprintf(
                'CONSTRAINT %s %s',
                $this->escapeIdentifier($foreignKeyConstraint->name),
                $sql
            );
        }

        foreach ($foreignKeyConstraint->referentialActions as $referentialAction) {
            $sql .= ' ';
            $sql .= is_subclass_of($referentialAction, BackedEnum::class)
                ? $referentialAction->value
                : (string) $referentialAction;
        }

        return $sql;
    }

    protected function buildAlterTableAddColumn(AddColumn $addColumn): string
    {
        return sprintf(
            'ADD COLUMN %s',
            $this->buildColumn($addColumn)
        );
    }

    protected function buildAlterTableAlterColumn(AlterColumn $alterColumn): string
    {
        return sprintf(
            'ALTER COLUMN %s %s',
            $this->escapeIdentifier($alterColumn->column),
            implode(
                ' ',
                $alterColumn->options
            )
        );
    }

    protected function buildAlterTableRenameColumn(RenameColumn $renameColumn): string
    {
        return sprintf(
            'RENAME COLUMN %s TO %s',
            $this->escapeIdentifier($renameColumn->old),
            $this->escapeIdentifier($renameColumn->new)
        );
    }

    protected function buildAlterTableDropColumn(DropColumn $dropColumn): string
    {
        return sprintf(
            'DROP COLUMN %s',
            $this->escapeIdentifier($dropColumn->column)
        );
    }

    protected function buildAlterTableAddPrimaryKeys(AddPrimaryKeys $addPrimaryKeys): string
    {
        return sprintf(
            'ADD PRIMARY KEY (%s)',
            implode(
                ', ',
                array_map(
                    fn (string|array|Raw $column): string => $this->escapeIdentifier($column),
                    $addPrimaryKeys->columns
                )
            )
        );
    }

    protected function buildAlterTableAddUniqueConstraint(AddUniqueConstraint $addUniqueConstraint): string
    {
        return sprintf(
            'ADD %s',
            $this->buildUniqueConstraint($addUniqueConstraint)
        );
    }

    protected function buildAlterTableAddForeignKeyConstraint(AddForeignKeyConstraint $addForeignKeyConstraint): string
    {
        return sprintf(
            'ADD %s',
            $this->buildForeignKeyConstraint($addForeignKeyConstraint)
        );
    }

    protected function buildAlterTableDropConstraint(DropConstraint $dropConstraint): string
    {
        return sprintf(
            'DROP CONSTRAINT %s',
            $this->escapeIdentifier($dropConstraint->constraint)
        );
    }

    protected function escapeIdentifierWithAlias(string|array|Raw $identifier, ?string $alias): string
    {
        $escapedIdentifier = $this->escapeIdentifier($identifier);

        if (!$alias) {
            return $escapedIdentifier;
        }

        return sprintf('%s AS %s', $escapedIdentifier, $this->escapeIdentifier($alias));
    }

    public function escapeIdentifier(string|array|Raw $identifier): string
    {
        if ($identifier instanceof Raw) {
            return (string) $identifier;
        }

        return is_array($identifier)
            ? implode(
                '.',
                array_map(
                    fn (string|array|Raw $identifier): string => $this->escapeIdentifier($identifier),
                    $identifier
                )
            )
            : $this->escape($identifier, $this::ESCAPE_IDENTIFIER);
    }

    public function escapeString(string $string): string
    {
        return $this->escape($string, $this::ESCAPE_STRING);
    }

    protected function escape(string $string, string $char): string
    {
        $escapedString = $this::ESCAPE_ANSI
            ? Query::escapeAnsi($string, [$char])
            : Query::escapeBackslash($string, [$char]);

        return $char . $escapedString . $char;
    }

    public function castToDriver(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $this->castBool($value);
        }

        if ($value instanceof DateTimeInterface) {
            return $this->castDateTime($value);
        }

        return $value;
    }

    public function castToQuery(mixed $value): mixed
    {
        if (is_string($value)) {
            return $this->escapeString($value);
        }

        if (is_bool($value)) {
            $bool = $this->castBool($value);

            return is_string($bool)
                ? $this->escapeString($bool)
                : $bool;
        }

        if (is_null($value)) {
            return 'NULL';
        }

        if ($value instanceof DateTimeInterface) {
            return $this->escapeString($this->castDateTime($value));
        }

        return $value;
    }

    public function castBool(bool $bool): mixed
    {
        return $bool ? 1 : 0;
    }

    public function castDateTime(DateTimeInterface $dateTimeInterface): mixed
    {
        return $dateTimeInterface->format($this::DATETIME_FORMAT);
    }

    public function parseBool(mixed $value): bool
    {
        return $value == 1 ? true : false;
    }

    public function parseDateTime(string $string): ?DateTime
    {
        $dateTime = DateTime::createFromFormat($this::DATETIME_FORMAT, $string);

        if ($dateTime) {
            return $dateTime;
        }

        try {
            return new DateTime($string);
        } catch (Throwable $exception) {
        }

        $timestamp = strtotime($string);

        if (!$timestamp) {
            return null;
        }

        $hasMicroseconds = preg_match('/\.([0-9]{0,6})[\+\-]?/', $string, $microsecondsMatches);

        $dateTime = DateTime::createFromFormat(
            'U.u',
            sprintf(
                '%d.%d',
                $timestamp,
                $hasMicroseconds ? (int) $microsecondsMatches[1] : 0
            )
        );

        if (!$dateTime) {
            return null;
        }

        $hasTimezoneOffset = preg_match('/([\+\-])([0-9]{1,2}+)\:?([0-9]{0,2})$/', $string, $timezoneOffsetMatches);

        if ($hasTimezoneOffset) {
            [$modifier, $timezoneOffsetHours, $timezoneOffsetMinutes] = array_slice($timezoneOffsetMatches, 1);

            $multiplier = ((int) $timezoneOffsetHours + (int) $timezoneOffsetMinutes / 60) * ($modifier == '+' ? 1 : -1);

            $timezone = timezone_name_from_abbr('', (int) ($multiplier * 3600));

            if ($timezone) {
                $dateTime->setTimezone(new DateTimeZone($timezone));
            }
        }

        return $dateTime;
    }

    public function onConflict(): bool
    {
        return static::ON_CONFLICT;
    }

    public function returning(): bool
    {
        return static::RETURNING;
    }
}
