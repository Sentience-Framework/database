<?php

namespace Sentience\Database\Dialects;

use BackedEnum;
use DateTime;
use DateTimeInterface;
use Throwable;
use Sentience\Database\Exceptions\QueryException;
use Sentience\Database\Queries\Enums\ConditionEnum;
use Sentience\Database\Queries\Enums\TypeEnum;
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
use Sentience\Database\Queries\Objects\Identifier;
use Sentience\Database\Queries\Objects\OnConflict;
use Sentience\Database\Queries\Objects\OrderBy;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Queries\Objects\RenameColumn;
use Sentience\Database\Queries\Objects\SubQuery;
use Sentience\Database\Queries\Objects\UniqueConstraint;
use Sentience\Database\Queries\SelectQuery;

class SQLDialect extends DialectAbstract
{
    public const string DATETIME_FORMAT = 'Y-m-d H:i:s';
    public const bool ESCAPE_ANSI = true;
    public const string ESCAPE_IDENTIFIER = '"';
    public const string ESCAPE_STRING = "'";
    public const array ESCAPE_CHARS = ["\0" => ''];
    public const bool BOOL = false;
    public const bool GENERATED_BY_DEFAULT_AS_IDENTITY = true;
    public const bool ON_CONFLICT = false;
    public const bool RETURNING = false;
    public const bool SAVEPOINTS = true;

    public function select(
        bool $distinct,
        array $columns,
        string|array|Alias|Raw|SubQuery $table,
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
                    function (string|array|Alias|Raw|SubQuery $column) use (&$params): string {
                        if ($column instanceof SubQuery) {
                            $column = $column->toAlias(function (SelectQuery $selectQuery) use (&$params): string {
                                return $this->buildSelectQuery($params, $selectQuery);
                            });
                        }

                        return $this->escapeIdentifier($column);
                    },
                    $columns
                )
            )
            : '*';

        $query .= ' FROM';

        $this->buildTable($query, $params, $table);
        $this->buildJoins($query, $params, $joins);
        $this->buildWhere($query, $params, $where);
        $this->buildGroupBy($query, $groupBy);
        $this->buildHaving($query, $params, $having);
        $this->buildOrderBy($query, $orderBy);
        $this->buildLimit($query, $limit, $offset);
        $this->buildOffset($query, $limit, $offset);

        return new QueryWithParams($query, $params);
    }

    public function insert(
        string|array|Raw $table,
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

        $this->buildTable($query, $params, $table);

        $query .= sprintf(
            ' (%s)',
            implode(
                ', ',
                array_map(
                    fn (string $column): string => $this->escapeIdentifier($column),
                    array_keys($values)
                )
            )
        );

        $query .= sprintf(
            ' VALUES (%s)',
            implode(
                ', ',
                array_map(
                    function (null|bool|int|float|string|DateTimeInterface|Identifier|Raw|SelectQuery $value) use (&$params): string {
                        return $value instanceof SelectQuery
                            ? $this->buildSelectQuery($params, $value)
                            : $this->buildQuestionMarks($params, $value);
                    },
                    $values
                )
            )
        );

        $this->buildOnConflict($query, $params, $onConflict, $values, $lastInsertId);
        $this->buildReturning($query, $returning);

        return new QueryWithParams($query, $params);
    }

    public function update(
        string|array|Raw $table,
        array $values,
        array $where,
        ?array $returning
    ): QueryWithParams {
        if (count($values) == 0) {
            throw new QueryException('no update values specified');
        }

        $query = 'UPDATE';
        $params = [];

        $this->buildTable($query, $params, $table);

        $query .= ' SET ';
        $query .= implode(
            ', ',
            array_map(
                function (null|bool|int|float|string|DateTimeInterface|Identifier|Raw|SelectQuery $value, string $key) use (&$params): string {
                    return sprintf(
                        '%s = %s',
                        $this->escapeIdentifier($key),
                        $value instanceof SelectQuery
                        ? $this->buildSelectQuery($params, $value)
                        : $this->buildQuestionMarks($params, $value)
                    );
                },
                $values,
                array_keys($values)
            )
        );

        $this->buildWhere($query, $params, $where);
        $this->buildReturning($query, $returning);

        return new QueryWithParams($query, $params);
    }

    public function delete(
        string|array|Raw $table,
        array $where,
        ?array $returning
    ): QueryWithParams {
        $query = 'DELETE FROM';
        $params = [];

        $this->buildTable($query, $params, $table);
        $this->buildWhere($query, $params, $where);
        $this->buildReturning($query, $returning);

        return new QueryWithParams($query, $params);
    }

    public function createTable(
        bool $ifNotExists,
        string|array|Raw $table,
        array $columns,
        array $primaryKeys,
        array $constraints
    ): QueryWithParams {
        if (count($columns) == 0) {
            throw new QueryException('no table columns specified');
        }

        $query = 'CREATE TABLE';
        $params = [];

        if ($ifNotExists) {
            $query .= ' IF NOT EXISTS';
        }

        $this->buildTable($query, $params, $table);

        $query .= ' (';

        foreach ($columns as $index => $column) {
            if ($index > 0) {
                $query .= ', ';
            }

            $query .= $this->buildColumn($column);
        }

        if (count($primaryKeys) > 0) {
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
        }

        foreach ($constraints as $constraint) {
            $query .= ', ';
            $query .= match (true) {
                $constraint instanceof UniqueConstraint => $this->buildUniqueConstraint($constraint),
                $constraint instanceof ForeignKeyConstraint => $this->buildForeignKeyConstraint($constraint),
                default => $constraint->sql
            };
        }

        $query .= ')';

        return new QueryWithParams($query, $params);
    }

    public function alterTable(
        string|array|Raw $table,
        array $alters
    ): array {
        if (count($alters) == 0) {
            throw new QueryException('no table alters specified');
        }

        return array_map(
            function (object $alter) use ($table): QueryWithParams {
                $query = 'ALTER TABLE';
                $params = [];

                $this->buildTable($query, $params, $table);

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
                    default => $alter->sql
                };

                return new QueryWithParams($query, $params);
            },
            $alters
        );
    }

    public function dropTable(
        bool $ifExists,
        string|array|Raw $table
    ): QueryWithParams {
        $query = 'DROP TABLE';
        $params = [];

        if ($ifExists) {
            $query .= ' IF EXISTS';
        }

        $this->buildTable($query, $params, $table);

        return new QueryWithParams($query, $params);
    }

    public function beginTransaction(
        ?string $name
    ): QueryWithParams {
        $query = 'BEGIN TRANSACTION';

        if ($name) {
            $query .= ' ';
            $query .= $this->escapeIdentifier($name);
        }

        return new QueryWithParams($query);
    }

    public function commitTransaction(
        ?string $name
    ): QueryWithParams {
        $query = 'COMMIT';

        if ($name) {
            $query .= ' ';
            $query .= $this->escapeIdentifier($name);
        }

        return new QueryWithParams($query);
    }

    public function rollbackTransaction(
        ?string $name
    ): QueryWithParams {
        $query = 'ROLLBACK';

        if ($name) {
            $query .= ' ';
            $query .= $this->escapeIdentifier($name);
        }

        return new QueryWithParams($query);
    }

    public function beginSavepoint(
        string $name
    ): QueryWithParams {
        return new QueryWithParams(
            sprintf(
                'SAVEPOINT %s',
                $this->escapeIdentifier($name)
            )
        );
    }

    public function commitSavepoint(
        string $name
    ): QueryWithParams {
        return new QueryWithParams(
            sprintf(
                'RELEASE SAVEPOINT %s',
                $this->escapeIdentifier($name)
            )
        );
    }

    public function rollbackSavepoint(
        string $name
    ): QueryWithParams {
        return new QueryWithParams(
            sprintf(
                'ROLLBACK TO %s',
                $this->escapeIdentifier($name)
            )
        );
    }

    protected function buildTable(string &$query, &$params, string|array|Alias|Raw|SubQuery $table): void
    {
        $query .= ' ';

        if ($table instanceof SubQuery) {
            $table = $table->toAlias(function (SelectQuery $selectQuery) use (&$params): string {
                return $this->buildSelectQuery($params, $selectQuery);
            });
        }

        $query .= $this->escapeIdentifier($table);
    }

    protected function buildJoins(string &$query, array &$params, array $joins): void
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
                $query .= $join->sql;

                continue;
            }

            $table = $join->table instanceof SubQuery
                ? $join->table->toAlias(function (SelectQuery $selectQuery) use (&$params): string {
                    return $this->buildSelectQuery($params, $selectQuery);
                })
                : $join->table;

            $query .= sprintf(
                '%s %s ON ',
                $join->join->value,
                $this->escapeIdentifier($table)
            );

            $conditions = $join->getConditions();

            if (count($conditions) == 0) {
                $query .= $this->castToQuery(true);

                continue;
            }

            foreach ($conditions as $index => $condition) {
                $condition instanceof Condition
                    ? $this->buildCondition($query, $params, $index, $condition)
                    : $this->buildConditionGroup($query, $params, $index, $condition);
            }
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
            ConditionEnum::EXISTS,
            ConditionEnum::NOT_EXISTS => $this->buildConditionExists($query, $params, $condition),
            ConditionEnum::RAW => $this->buildConditionRaw($query, $params, $condition),
            default => $this->buildConditionOperator($query, $params, $condition)
        };
    }

    protected function buildConditionOperator(string &$query, array &$params, Condition $condition): void
    {
        if (is_null($condition->value) && in_array($condition->condition, [ConditionEnum::EQUALS, ConditionEnum::NOT_EQUALS])) {
            $query .= sprintf(
                '%s %s',
                $this->escapeIdentifier($condition->identifier),
                $condition->condition == ConditionEnum::EQUALS ? 'IS NULL' : 'IS NOT NULL'
            );

            return;
        }

        $operator = is_subclass_of($condition->condition, BackedEnum::class)
            ? $condition->condition->value
            : $condition->condition;

        $value = $condition->value instanceof SelectQuery
            ? $this->buildSelectQuery($params, $condition->value)
            : $this->buildQuestionMarks($params, $condition->value);

        $query .= sprintf(
            '%s %s %s',
            $this->escapeIdentifier($condition->identifier),
            $operator,
            $value
        );
    }

    protected function buildConditionBetween(string &$query, array &$params, Condition $condition): void
    {
        $min = $condition->value[0] instanceof SelectQuery
            ? $this->buildSelectQuery($params, $condition->value[0])
            : $this->buildQuestionMarks($params, $condition->value[0]);

        $max = $condition->value[1] instanceof SelectQuery
            ? $this->buildSelectQuery($params, $condition->value[1])
            : $this->buildQuestionMarks($params, $condition->value[1]);

        $query .= sprintf(
            '%s %s %s AND %s',
            $this->escapeIdentifier($condition->identifier),
            $condition->condition->value,
            $min,
            $max
        );
    }

    protected function buildConditionLike(string &$query, array &$params, Condition $condition): void
    {
        [$value, $caseInsensitive] = $condition->value;

        $identifier = $condition->identifier;
        $questionMark = $this->buildQuestionMarks($params, $value);

        $query .= sprintf(
            '%s %s %s',
            $caseInsensitive ? sprintf('lower(%s)', $identifier) : $identifier,
            $condition->condition->value,
            $caseInsensitive ? sprintf('lower(%s)', $questionMark) : $questionMark
        );
    }

    protected function buildConditionIn(string &$query, array &$params, Condition $condition): void
    {
        if (!($condition->value instanceof SelectQuery) && count($condition->value) == 0) {
            $query .= $condition->condition == ConditionEnum::IN ? '1 <> 1' : '1 = 1';

            return;
        }

        $this->buildConditionOperator($query, $params, $condition);
    }

    protected function buildConditionRegex(string &$query, array &$params, Condition $condition): void
    {
        if ($condition->condition == ConditionEnum::NOT_REGEX) {
            $query .= 'NOT ';
        }

        $query .= sprintf(
            'regexp_like(%s, %s, %s)',
            $this->escapeIdentifier($condition->identifier),
            $this->buildQuestionMarks($params, $condition->value[0]),
            $this->buildQuestionMarks($params, $condition->value[1])
        );
    }

    protected function buildConditionRegexOperator(string &$query, array &$params, Condition $condition, string $equals, string $notEquals): void
    {
        [$pattern, $flags] = $condition->value;

        $query .= sprintf(
            '%s %s %s',
            $this->escapeIdentifier($condition->identifier),
            $condition->condition == ConditionEnum::REGEX ? $equals : $notEquals,
            $this->buildQuestionMarks(
                $params,
                !empty($flags)
                ? sprintf('(?%s)%s', $flags, $pattern)
                : $pattern
            )
        );
    }

    protected function buildConditionExists(string &$query, array &$params, Condition $condition): void
    {
        $query .= sprintf(
            '%s %s',
            $condition->condition->value,
            $this->buildSelectQuery($params, $condition->value)
        );
    }

    protected function buildConditionRaw(string &$query, array &$params, Condition $condition): void
    {
        $query .= sprintf('(%s)', $condition->identifier);

        array_push($params, ...$condition->value);
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
        if (!$this->onConflict()) {
            return;
        }

        if (is_null($onConflict)) {
            return;
        }

        $conflict = is_string($onConflict->conflict)
            ? sprintf('ON CONSTRAINT %s', $this->escapeIdentifier($onConflict->conflict))
            : sprintf(
                '(%s)',
                implode(
                    ', ',
                    array_map(
                        fn (string|Raw $column): string => $this->escapeIdentifier($column),
                        $onConflict->conflict
                    )
                )
            );

        $query .= sprintf(' ON CONFLICT %s DO ', $conflict);

        if (is_null($onConflict->updates)) {
            $query .= 'NOTHING';

            return;
        }

        $updates = count($onConflict->updates) > 0 ? $onConflict->updates : $values;

        $query .= sprintf(
            'UPDATE SET %s',
            implode(
                ', ',
                array_map(
                    function (null|bool|int|float|string|DateTimeInterface|Identifier|Raw|SelectQuery $value, string $key) use (&$params): string {
                        return sprintf(
                            '%s = %s',
                            $this->escapeIdentifier($key),
                            $value instanceof SelectQuery
                            ? $this->buildSelectQuery($params, $value)
                            : $this->buildQuestionMarks($params, $value)
                        );
                    },
                    $updates,
                    array_keys($updates)
                )
            )
        );
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

    protected function buildQuestionMarks(array &$params, null|bool|int|float|string|array|DateTimeInterface|Identifier|Raw $value, bool $parentheses = true, string $separator = ', '): string
    {
        if ($value instanceof Identifier) {
            return $this->escapeIdentifier($value->identifier);
        }

        if ($value instanceof Raw) {
            return $value->sql;
        }

        if (is_array($value)) {
            return sprintf(
                $parentheses ? '(%s)' : '%s',
                implode(
                    $separator,
                    array_map(
                        function (null|bool|int|float|string|DateTimeInterface|Identifier|Raw|SelectQuery $value) use (&$params): string {
                            return $value instanceof SelectQuery
                                ? $this->buildSelectQuery($params, $value)
                                : $this->buildQuestionMarks($params, $value);
                        },
                        $value
                    )
                )
            );
        }

        array_push($params, $value);

        return '?';
    }

    protected function buildSelectQuery(array &$params, SelectQuery $selectQuery): string
    {
        $queryWithParams = $selectQuery->toQueryWithParams();

        array_push($params, ...$queryWithParams->params);

        return sprintf(
            '(%s)',
            $queryWithParams->query
        );
    }

    protected function buildColumn(Column $column): string
    {
        $sql = sprintf(
            '%s %s',
            $this->escapeIdentifier($column->name),
            $column->type
        );

        if ($column->generatedByDefaultAsIdentity && $this->generatedByDefaultAsIdentity()) {
            $sql .= ' GENERATED BY DEFAULT AS IDENTITY';
        }

        if ($column->notNull) {
            $sql .= ' NOT NULL';
        }

        if (!is_null($column->default)) {
            $default = !($column->default instanceof Raw)
                ? $this->castToQuery($column->default)
                : $column->default->sql;

            $sql .= ' DEFAULT ' . $default;
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
            $sql .= (string) is_subclass_of($referentialAction, BackedEnum::class)
                ? $referentialAction->value
                : $referentialAction;
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
            $alterColumn->sql
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

    public function escapeIdentifier(string|array|Alias|Raw $identifier): string
    {
        if ($identifier instanceof Alias) {
            return sprintf(
                '%s AS %s',
                $this->escapeIdentifier($identifier->identifier),
                $this->escapeIdentifier($identifier->alias)
            );
        }

        if ($identifier instanceof Raw) {
            return $identifier->sql;
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
        $escaped = strtr(
            $string,
            [
                ...static::ESCAPE_CHARS,
                $char => static::ESCAPE_ANSI
                    ? sprintf('%s%s', $char, $char)
                    : sprintf('\\%s', $char, $char)
            ]
        );

        return $char . $escaped . $char;
    }

    public function castToDriver(null|bool|int|float|string|DateTimeInterface $value): null|bool|int|float|string
    {
        if (is_bool($value)) {
            return $this->castBool($value);
        }

        if ($value instanceof DateTimeInterface) {
            return $this->castDateTime($value);
        }

        return $value;
    }

    public function castToQuery(null|bool|int|float|string|DateTimeInterface $value): string
    {
        if (is_null($value)) {
            return 'NULL';
        }

        if (is_bool($value)) {
            if ($this->bool()) {
                return $value ? 'TRUE' : 'FALSE';
            }

            $bool = $this->castBool($value);

            return is_string($bool)
                ? $this->escapeString($bool)
                : (string) $bool;
        }

        if (is_string($value)) {
            return $this->escapeString($value);
        }

        if ($value instanceof DateTimeInterface) {
            return $this->escapeString($this->castDateTime($value));
        }

        return (string) $value;
    }

    public function castBool(bool $bool): null|bool|int|float|string
    {
        return !$this->bool()
            ? ($bool ? 1 : 0)
            : $bool;
    }

    public function castDateTime(DateTimeInterface $dateTimeInterface): null|bool|int|float|string
    {
        return $dateTimeInterface->format($this::DATETIME_FORMAT);
    }

    public function parseBool(null|bool|int|float|string $bool): bool
    {
        if (is_bool($bool)) {
            return $bool;
        }

        return (int) $bool > 0;
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
            return null;
        }
    }

    public function type(TypeEnum $type, ?int $size = null): string
    {
        return match ($type) {
            TypeEnum::BOOL => $this->bool() ? 'BOOLEAN' : 'INTEGER',
            TypeEnum::INT => $size > 32 ? 'BIGINT' : 'INTEGER',
            TypeEnum::FLOAT => $size > 32 ? 'DECIMAL(30, 15)' : 'DECIMAL(15, 7)',
            TypeEnum::STRING => $size > 255 ? 'TEXT' : sprintf('VARCHAR(%d)', $size ?? 255),
            TypeEnum::DATETIME => 'DATETIME'
        };
    }

    public function bool(): bool
    {
        return static::BOOL;
    }

    public function generatedByDefaultAsIdentity(): bool
    {
        return static::GENERATED_BY_DEFAULT_AS_IDENTITY;
    }

    public function onConflict(): bool
    {
        return static::ON_CONFLICT;
    }

    public function returning(): bool
    {
        return static::RETURNING;
    }

    public function savepoints(): bool
    {
        return static::SAVEPOINTS;
    }
}
