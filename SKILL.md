# \Sentience\Database\Driver
Driver::FIREBIRD = 'firebird';
Driver::MARIADB = 'mariadb';
Driver::MYSQL = 'mysql';
Driver::OCI = 'oci';
Driver::PGSQL = 'pgsql';
Driver::SQLITE = 'sqlite';
Driver::SQLSRV = 'sqlsrv';

# Database
```php
// Classes:
// \Sentience\Database\Databases\Firebird\FirebirdDatabase
// \Sentience\Database\Databases\MySQL\MySQLDatabase
// \Sentience\Database\Databases\MySQL\MariaDBDatabase
// \Sentience\Database\Databases\OCI\OCIDatabase
// \Sentience\Database\Databases\PgSQL\PgSQLDatabase
// \Sentience\Database\Databases\SQLite\SQLiteDatabase
// \Sentience\Database\Databases\SQLServer\SQLServerDatabase
// \Sentience\Database\Databases\Firebird\FirebirdDatabase
// \Sentience\Database\Databases\Firebird\FirebirdDatabase
// \Sentience\Database\Databases\Firebird\FirebirdDatabase
// \Sentience\Database\Databases\DatabaseAbstract
// \Sentience\Database\Databases\DatabaseInterface
// \Sentience\Database\Database (Generic database class that connects to all databases, but lacks database specific schema functions)

DatabaseInterface->exec(string $query): void
DatabaseInterface->query(string $query): \Sentience\Database\Results\ResultInterface
DatabaseInterface->prepared(string $query, array $params, bool $emulatePrepare): \Sentience\Database\Results\ResultInterface
DatabaseInterface->queryWithParams(\Sentience\Database\Queries\Objects\QueryWithParams $queryWithParams, bool $emulatePrepare): \Sentience\Database\Results\ResultInterface
DatabaseInterface->beginTransaction(?string $name): void
DatabaseInterface->commitTransaction(bool $releaseSavepoints, ?string $name): void
DatabaseInterface->rollbackTransaction(bool $releaseSavepoints, ?string $name): void
DatabaseInterface->inTransaction(): bool
DatabaseInterface->transaction(callable $callback, bool $releaseSavepoints, ?string $name): mixed
DatabaseInterface->lastInsertId(?string $name): string|int|null
DatabaseInterface->select(\Sentience\Database\Queries\Objects\Alias|Sentience\Database\Queries\Interfaces\Sql|Sentience\Database\Queries\Objects\SubQuery|array|string $table): \Sentience\Database\Queries\SelectQuery
DatabaseInterface->insert(\Sentience\Database\Queries\Interfaces\Sql|array|string $table): \Sentience\Database\Queries\InsertQuery
DatabaseInterface->update(\Sentience\Database\Queries\Interfaces\Sql|array|string $table): \Sentience\Database\Queries\UpdateQuery
DatabaseInterface->delete(\Sentience\Database\Queries\Interfaces\Sql|array|string $table): \Sentience\Database\Queries\DeleteQuery
DatabaseInterface->createTable(\Sentience\Database\Queries\Interfaces\Sql|array|string $table): \Sentience\Database\Queries\CreateTableQuery
DatabaseInterface->alterTable(\Sentience\Database\Queries\Interfaces\Sql|array|string $table): \Sentience\Database\Queries\AlterTableQuery
DatabaseInterface->dropTable(\Sentience\Database\Queries\Interfaces\Sql|array|string $table): \Sentience\Database\Queries\DropTableQuery
DatabaseInterface->table(\Sentience\Database\Queries\Interfaces\Sql|array|string $table): \Sentience\Database\Queries\Table
DatabaseInterface->addWhereMacro(string $macro, callable $callback): static
DatabaseInterface->getAvailableMutableStoredProcedures(): array
DatabaseInterface->getAvailableImmutableStoredProcedures(): array
DatabaseInterface->createMutableStoredProcedure(string $name, callable $callback): void
DatabaseInterface->createImmutableStoredProcedure(string $name, string $query): void
DatabaseInterface->executeMutableStoredProcedure(string $name, array $params, ?callable $callback, bool $emulatePrepare): \Sentience\Database\Results\ResultInterface
DatabaseInterface->executeImmutableStoredProcedure(string $name, array $params, bool $emulatePrepare): \Sentience\Database\Results\ResultInterface
```

# \Sentience\Database\Queries\Query
```php
Query::alias(\Sentience\Database\Queries\Interfaces\Sql|array|string $identifier, string $alias): \Sentience\Database\Queries\Objects\Alias
Query::expression(string $sql, array $params): \Sentience\Database\Queries\Objects\Expression
Query::identifier(array|string $identifier): \Sentience\Database\Queries\Objects\Identifier
Query::raw(string $sql): \Sentience\Database\Queries\Objects\Raw
Query::subQuery(\Sentience\Database\Queries\SelectQuery $selectQuery, string $alias): \Sentience\Database\Queries\Objects\SubQuery
Query::now(): DateTime
Query::escapeAnsi(string $string, array $chars): string
Query::escapeBackslash(string $string, array $chars): string
Query::escapeLikeChars(string $string, bool $escapeBackslash): string
```

# \Sentience\Database\Queries\SelectQuery
```php
SelectQuery->toSql(): string
SelectQuery->execute(bool $emulatePrepare): \Sentience\Database\Results\ResultInterface
SelectQuery->from(\Sentience\Database\Queries\Objects\Alias|Sentience\Database\Queries\Interfaces\Sql|Sentience\Database\Queries\Objects\SubQuery|array|string $table): static
SelectQuery->count(\Sentience\Database\Queries\Interfaces\Sql|array|string|null $column, bool $emulatePrepare): int
SelectQuery->explain(bool $emulatePrepare): array
SelectQuery->columns(array $columns): static
SelectQuery->distinct(): static
SelectQuery->groupBy(array $columns): static
SelectQuery->having(string $conditions, array $values): static
SelectQuery->leftJoin(\Sentience\Database\Queries\Objects\Alias|Sentience\Database\Queries\Interfaces\Sql|Sentience\Database\Queries\Objects\SubQuery|array|string $table, ?callable $on): static
SelectQuery->innerJoin(\Sentience\Database\Queries\Objects\Alias|Sentience\Database\Queries\Interfaces\Sql|Sentience\Database\Queries\Objects\SubQuery|array|string $table, ?callable $on): static
SelectQuery->join(string $join): static
SelectQuery->limit(int $limit): static
SelectQuery->offset(int $offset): static
SelectQuery->orderByAsc(\Sentience\Database\Queries\Interfaces\Sql|array|string $column): static
SelectQuery->orderByDesc(\Sentience\Database\Queries\Interfaces\Sql|array|string $column): static
SelectQuery->union(\Sentience\Database\Queries\SelectQuery $selectQuery): static
SelectQuery->unionAll(\Sentience\Database\Queries\SelectQuery $selectQuery): static
SelectQuery->whereEquals(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float|bool|null $value): static
SelectQuery->whereNotEquals(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float|bool|null $value): static
SelectQuery->whereIsNull(array|string $column): static
SelectQuery->whereIsNotNull(array|string $column): static
SelectQuery->whereLike(array|string $column, string $value, bool $caseInsensitive): static
SelectQuery->whereNotLike(array|string $column, string $value, bool $caseInsensitive): static
SelectQuery->whereStartsWith(array|string $column, string $value, bool $caseInsensitive, bool $escapeBackslash): static
SelectQuery->whereEndsWith(array|string $column, string $value, bool $caseInsensitive, bool $escapeBackslash): static
SelectQuery->whereContains(array|string $column, string $value, bool $caseInsensitive, bool $escapeBackslash): static
SelectQuery->whereNotContains(array|string $column, string $value, bool $caseInsensitive, bool $escapeBackslash): static
SelectQuery->whereIn(array|string $column, \Sentience\Database\Queries\SelectQuery|array $values): static
SelectQuery->whereNotIn(array|string $column, \Sentience\Database\Queries\SelectQuery|array $values): static
SelectQuery->whereLessThan(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $value): static
SelectQuery->whereLessThanOrEquals(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $value): static
SelectQuery->whereGreaterThan(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $value): static
SelectQuery->whereGreaterThanOrEquals(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $value): static
SelectQuery->whereBetween(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $min, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $max): static
SelectQuery->whereNotBetween(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $min, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $max): static
SelectQuery->whereEmpty(array|string $column): static
SelectQuery->whereNotEmpty(array|string $column): static
SelectQuery->whereRegex(array|string $column, string $pattern, string $flags): static
SelectQuery->whereNotRegex(array|string $column, string $pattern, string $flags): static
SelectQuery->whereExists(\Sentience\Database\Queries\SelectQuery $selectQuery): static
SelectQuery->whereNotExists(\Sentience\Database\Queries\SelectQuery $selectQuery): static
SelectQuery->whereGroup(callable $callback): static
SelectQuery->whereOperator(array|string $column, BackedEnum|string $operator, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|array|string|int|float|bool|null $value): static
SelectQuery->where(string $sql, array $values): static
SelectQuery->whereMacro(BackedEnum|string $macro, array $args): static
SelectQuery->orWhereEquals(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float|bool|null $value): static
SelectQuery->orWhereNotEquals(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float|bool|null $value): static
SelectQuery->orWhereIsNull(array|string $column): static
SelectQuery->orWhereIsNotNull(array|string $column): static
SelectQuery->orWhereLike(array|string $column, string $value, bool $caseInsensitive): static
SelectQuery->orWhereNotLike(array|string $column, string $value, bool $caseInsensitive): static
SelectQuery->orWhereStartsWith(array|string $column, string $value, bool $caseInsensitive, bool $escapeBackslash): static
SelectQuery->orWhereEndsWith(array|string $column, string $value, bool $caseInsensitive, bool $escapeBackslash): static
SelectQuery->orWhereContains(array|string $column, string $value, bool $caseInsensitive, bool $escapeBackslash): static
SelectQuery->orWhereNotContains(array|string $column, string $value, bool $caseInsensitive, bool $escapeBackslash): static
SelectQuery->orWhereIn(array|string $column, \Sentience\Database\Queries\SelectQuery|array $values): static
SelectQuery->orWhereNotIn(array|string $column, \Sentience\Database\Queries\SelectQuery|array $values): static
SelectQuery->orWhereLessThan(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $value): static
SelectQuery->orWhereLessThanOrEquals(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $value): static
SelectQuery->orWhereGreaterThan(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $value): static
SelectQuery->orWhereGreaterThanOrEquals(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $value): static
SelectQuery->orWhereBetween(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $min, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $max): static
SelectQuery->orWhereNotBetween(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $min, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $max): static
SelectQuery->orWhereEmpty(array|string $column): static
SelectQuery->orWhereNotEmpty(array|string $column): static
SelectQuery->orWhereRegex(array|string $column, string $pattern, string $flags): static
SelectQuery->orWhereNotRegex(array|string $column, string $pattern, string $flags): static
SelectQuery->orWhereExists(\Sentience\Database\Queries\SelectQuery $selectQuery): static
SelectQuery->orWhereNotExists(\Sentience\Database\Queries\SelectQuery $selectQuery): static
SelectQuery->orWhereGroup(callable $callback): static
SelectQuery->orWhereOperator(array|string $column, BackedEnum|string $operator, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|array|string|int|float|bool|null $value): static
SelectQuery->orWhere(string $sql, array $values): static
SelectQuery->orWhereMacro(BackedEnum|string $macro, array $args): static
```

# \Sentience\Database\Queries\InsertQuery
```php
InsertQuery->toSql(): string
InsertQuery->execute(bool $emulatePrepare): \Sentience\Database\Results\ResultInterface
InsertQuery->into(\Sentience\Database\Queries\Interfaces\Sql|array|string $table): static
InsertQuery->emulateOnConflict(string $lastInsertId, bool $inTransaction): static
InsertQuery->emulateReturning(string $lastInsertId): static
InsertQuery->explain(bool $emulatePrepare): array
InsertQuery->lastInsertId(string $column): static
InsertQuery->onConflictIgnore(array|string $conflict): static
InsertQuery->onConflictUpdate(array|string $conflict, array $updates): static
InsertQuery->returning(array $columns): static
InsertQuery->values(array $values): static
```

# \Sentience\Database\Queries\UpdateQuery
```php
UpdateQuery->toSql(): string
UpdateQuery->execute(bool $emulatePrepare): \Sentience\Database\Results\ResultInterface
UpdateQuery->explain(bool $emulatePrepare): array
UpdateQuery->returning(array $columns): static
UpdateQuery->values(array $values): static
UpdateQuery->whereEquals(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float|bool|null $value): static
UpdateQuery->whereNotEquals(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float|bool|null $value): static
UpdateQuery->whereIsNull(array|string $column): static
UpdateQuery->whereIsNotNull(array|string $column): static
UpdateQuery->whereLike(array|string $column, string $value, bool $caseInsensitive): static
UpdateQuery->whereNotLike(array|string $column, string $value, bool $caseInsensitive): static
UpdateQuery->whereStartsWith(array|string $column, string $value, bool $caseInsensitive, bool $escapeBackslash): static
UpdateQuery->whereEndsWith(array|string $column, string $value, bool $caseInsensitive, bool $escapeBackslash): static
UpdateQuery->whereContains(array|string $column, string $value, bool $caseInsensitive, bool $escapeBackslash): static
UpdateQuery->whereNotContains(array|string $column, string $value, bool $caseInsensitive, bool $escapeBackslash): static
UpdateQuery->whereIn(array|string $column, \Sentience\Database\Queries\SelectQuery|array $values): static
UpdateQuery->whereNotIn(array|string $column, \Sentience\Database\Queries\SelectQuery|array $values): static
UpdateQuery->whereLessThan(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $value): static
UpdateQuery->whereLessThanOrEquals(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $value): static
UpdateQuery->whereGreaterThan(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $value): static
UpdateQuery->whereGreaterThanOrEquals(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $value): static
UpdateQuery->whereBetween(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $min, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $max): static
UpdateQuery->whereNotBetween(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $min, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $max): static
UpdateQuery->whereEmpty(array|string $column): static
UpdateQuery->whereNotEmpty(array|string $column): static
UpdateQuery->whereRegex(array|string $column, string $pattern, string $flags): static
UpdateQuery->whereNotRegex(array|string $column, string $pattern, string $flags): static
UpdateQuery->whereExists(\Sentience\Database\Queries\SelectQuery $selectQuery): static
UpdateQuery->whereNotExists(\Sentience\Database\Queries\SelectQuery $selectQuery): static
UpdateQuery->whereGroup(callable $callback): static
UpdateQuery->whereOperator(array|string $column, BackedEnum|string $operator, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|array|string|int|float|bool|null $value): static
UpdateQuery->where(string $sql, array $values): static
UpdateQuery->whereMacro(BackedEnum|string $macro, array $args): static
UpdateQuery->orWhereEquals(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float|bool|null $value): static
UpdateQuery->orWhereNotEquals(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float|bool|null $value): static
UpdateQuery->orWhereIsNull(array|string $column): static
UpdateQuery->orWhereIsNotNull(array|string $column): static
UpdateQuery->orWhereLike(array|string $column, string $value, bool $caseInsensitive): static
UpdateQuery->orWhereNotLike(array|string $column, string $value, bool $caseInsensitive): static
UpdateQuery->orWhereStartsWith(array|string $column, string $value, bool $caseInsensitive, bool $escapeBackslash): static
UpdateQuery->orWhereEndsWith(array|string $column, string $value, bool $caseInsensitive, bool $escapeBackslash): static
UpdateQuery->orWhereContains(array|string $column, string $value, bool $caseInsensitive, bool $escapeBackslash): static
UpdateQuery->orWhereNotContains(array|string $column, string $value, bool $caseInsensitive, bool $escapeBackslash): static
UpdateQuery->orWhereIn(array|string $column, \Sentience\Database\Queries\SelectQuery|array $values): static
UpdateQuery->orWhereNotIn(array|string $column, \Sentience\Database\Queries\SelectQuery|array $values): static
UpdateQuery->orWhereLessThan(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $value): static
UpdateQuery->orWhereLessThanOrEquals(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $value): static
UpdateQuery->orWhereGreaterThan(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $value): static
UpdateQuery->orWhereGreaterThanOrEquals(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $value): static
UpdateQuery->orWhereBetween(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $min, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $max): static
UpdateQuery->orWhereNotBetween(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $min, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $max): static
UpdateQuery->orWhereEmpty(array|string $column): static
UpdateQuery->orWhereNotEmpty(array|string $column): static
UpdateQuery->orWhereRegex(array|string $column, string $pattern, string $flags): static
UpdateQuery->orWhereNotRegex(array|string $column, string $pattern, string $flags): static
UpdateQuery->orWhereExists(\Sentience\Database\Queries\SelectQuery $selectQuery): static
UpdateQuery->orWhereNotExists(\Sentience\Database\Queries\SelectQuery $selectQuery): static
UpdateQuery->orWhereGroup(callable $callback): static
UpdateQuery->orWhereOperator(array|string $column, BackedEnum|string $operator, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|array|string|int|float|bool|null $value): static
UpdateQuery->orWhere(string $sql, array $values): static
UpdateQuery->orWhereMacro(BackedEnum|string $macro, array $args): static
```

# \Sentience\Database\Queries\DeleteQuery
```php
DeleteQuery->toSql(): string
DeleteQuery->execute(bool $emulatePrepare): \Sentience\Database\Results\ResultInterface
DeleteQuery->from(\Sentience\Database\Queries\Interfaces\Sql|array|string $table): static
DeleteQuery->explain(bool $emulatePrepare): array
DeleteQuery->returning(array $columns): static
DeleteQuery->whereEquals(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float|bool|null $value): static
DeleteQuery->whereNotEquals(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float|bool|null $value): static
DeleteQuery->whereIsNull(array|string $column): static
DeleteQuery->whereIsNotNull(array|string $column): static
DeleteQuery->whereLike(array|string $column, string $value, bool $caseInsensitive): static
DeleteQuery->whereNotLike(array|string $column, string $value, bool $caseInsensitive): static
DeleteQuery->whereStartsWith(array|string $column, string $value, bool $caseInsensitive, bool $escapeBackslash): static
DeleteQuery->whereEndsWith(array|string $column, string $value, bool $caseInsensitive, bool $escapeBackslash): static
DeleteQuery->whereContains(array|string $column, string $value, bool $caseInsensitive, bool $escapeBackslash): static
DeleteQuery->whereNotContains(array|string $column, string $value, bool $caseInsensitive, bool $escapeBackslash): static
DeleteQuery->whereIn(array|string $column, \Sentience\Database\Queries\SelectQuery|array $values): static
DeleteQuery->whereNotIn(array|string $column, \Sentience\Database\Queries\SelectQuery|array $values): static
DeleteQuery->whereLessThan(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $value): static
DeleteQuery->whereLessThanOrEquals(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $value): static
DeleteQuery->whereGreaterThan(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $value): static
DeleteQuery->whereGreaterThanOrEquals(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $value): static
DeleteQuery->whereBetween(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $min, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $max): static
DeleteQuery->whereNotBetween(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $min, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $max): static
DeleteQuery->whereEmpty(array|string $column): static
DeleteQuery->whereNotEmpty(array|string $column): static
DeleteQuery->whereRegex(array|string $column, string $pattern, string $flags): static
DeleteQuery->whereNotRegex(array|string $column, string $pattern, string $flags): static
DeleteQuery->whereExists(\Sentience\Database\Queries\SelectQuery $selectQuery): static
DeleteQuery->whereNotExists(\Sentience\Database\Queries\SelectQuery $selectQuery): static
DeleteQuery->whereGroup(callable $callback): static
DeleteQuery->whereOperator(array|string $column, BackedEnum|string $operator, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|array|string|int|float|bool|null $value): static
DeleteQuery->where(string $sql, array $values): static
DeleteQuery->whereMacro(BackedEnum|string $macro, array $args): static
DeleteQuery->orWhereEquals(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float|bool|null $value): static
DeleteQuery->orWhereNotEquals(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float|bool|null $value): static
DeleteQuery->orWhereIsNull(array|string $column): static
DeleteQuery->orWhereIsNotNull(array|string $column): static
DeleteQuery->orWhereLike(array|string $column, string $value, bool $caseInsensitive): static
DeleteQuery->orWhereNotLike(array|string $column, string $value, bool $caseInsensitive): static
DeleteQuery->orWhereStartsWith(array|string $column, string $value, bool $caseInsensitive, bool $escapeBackslash): static
DeleteQuery->orWhereEndsWith(array|string $column, string $value, bool $caseInsensitive, bool $escapeBackslash): static
DeleteQuery->orWhereContains(array|string $column, string $value, bool $caseInsensitive, bool $escapeBackslash): static
DeleteQuery->orWhereNotContains(array|string $column, string $value, bool $caseInsensitive, bool $escapeBackslash): static
DeleteQuery->orWhereIn(array|string $column, \Sentience\Database\Queries\SelectQuery|array $values): static
DeleteQuery->orWhereNotIn(array|string $column, \Sentience\Database\Queries\SelectQuery|array $values): static
DeleteQuery->orWhereLessThan(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $value): static
DeleteQuery->orWhereLessThanOrEquals(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $value): static
DeleteQuery->orWhereGreaterThan(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $value): static
DeleteQuery->orWhereGreaterThanOrEquals(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $value): static
DeleteQuery->orWhereBetween(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $min, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $max): static
DeleteQuery->orWhereNotBetween(array|string $column, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $min, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|string|int|float $max): static
DeleteQuery->orWhereEmpty(array|string $column): static
DeleteQuery->orWhereNotEmpty(array|string $column): static
DeleteQuery->orWhereRegex(array|string $column, string $pattern, string $flags): static
DeleteQuery->orWhereNotRegex(array|string $column, string $pattern, string $flags): static
DeleteQuery->orWhereExists(\Sentience\Database\Queries\SelectQuery $selectQuery): static
DeleteQuery->orWhereNotExists(\Sentience\Database\Queries\SelectQuery $selectQuery): static
DeleteQuery->orWhereGroup(callable $callback): static
DeleteQuery->orWhereOperator(array|string $column, BackedEnum|string $operator, \DateTimeInterface|Sentience\Database\Queries\SelectQuery|Sentience\Database\Queries\Interfaces\Sql|array|string|int|float|bool|null $value): static
DeleteQuery->orWhere(string $sql, array $values): static
DeleteQuery->orWhereMacro(BackedEnum|string $macro, array $args): static
```

# \Sentience\Database\Queries\CreateTableQuery
```php
CreateTableQuery->toSql(): string
CreateTableQuery->execute(bool $emulatePrepare): \Sentience\Database\Results\ResultInterface
CreateTableQuery->column(string $name, string $type, bool $notNull, \DateTimeInterface|Sentience\Database\Queries\Interfaces\Sql|string|int|float|bool|null $default, bool $generatedByDefaultAsIdentity): static

// Recommended when adding columns to a table. These types are translated by the dialect to the appropriate column type
CreateTableQuery->autoIncrement(string $name, int $bits, bool $addPrimaryKey): static
CreateTableQuery->identity(string $name, int $bits, bool $addPrimaryKey): static
CreateTableQuery->bool(string $name, bool $notNull, \Sentience\Database\Queries\Interfaces\Sql|bool|null $default): static
CreateTableQuery->int(string $name, int $bits, bool $notNull, \Sentience\Database\Queries\Interfaces\Sql|int|null $default, bool $generatedByDefaultAsIdentity): static
CreateTableQuery->float(string $name, int $bits, bool $notNull, \DateTimeInterface|Sentience\Database\Queries\Interfaces\Sql|int|float|null $default): static
CreateTableQuery->string(string $name, int $size, bool $notNull, \Sentience\Database\Queries\Interfaces\Sql|string|null $default): static
CreateTableQuery->dateTime(string $name, int $size, bool $notNull, \DateTimeInterface|Sentience\Database\Queries\Interfaces\Sql|null $default): static

CreateTableQuery->uniqueConstraint(array $columns, ?string $name): static
CreateTableQuery->foreignKeyConstraint(string $column, string $referenceTable, string $referenceColumn, ?string $name, array $referentialActions): static
CreateTableQuery->constraint(string $sql): static
CreateTableQuery->ifNotExists(): static
CreateTableQuery->primaryKeys(array|string $columns): static
```

# \Sentience\Database\Queries\AlterTableQuery
```php
AlterTableQuery->toSql(): array
AlterTableQuery->execute(bool $emulatePrepares): array
AlterTableQuery->addColumn(string $name, string $type, bool $notNull, \DateTimeInterface|Sentience\Database\Queries\Interfaces\Sql|string|int|float|bool|null $default, bool $generatedByDefaultAsIdentity): static
AlterTableQuery->renameColumn(string $old, string $new): static
AlterTableQuery->dropColumn(string $column): static
AlterTableQuery->alter(string $sql): static


// Unsupported by SQLite
AlterTableQuery->alterColumn(string $column, string $sql): static
AlterTableQuery->addPrimaryKeys(array|string $columns): static
AlterTableQuery->addUniqueConstraint(array $columns, ?string $name): static
AlterTableQuery->addForeignKeyConstraint(string $column, string $referenceTable, string $referenceColumn, ?string $name, array $referentialActions): static
AlterTableQuery->dropConstraint(string $constraint): static

// Recommended when adding columns to a table. These types are translated by the dialect to the appropriate column type
AlterTableQuery->addAutoIncrement(string $name, int $bits, bool $addPrimaryKey): static
AlterTableQuery->addIdentity(string $name, int $bits, bool $addPrimaryKey): static
AlterTableQuery->addBool(string $name, bool $notNull, \Sentience\Database\Queries\Interfaces\Sql|bool|null $default): static
AlterTableQuery->addInt(string $name, int $bits, bool $notNull, \Sentience\Database\Queries\Interfaces\Sql|int|null $default, bool $generatedByDefaultAsIdentity): static
AlterTableQuery->addFloat(string $name, int $bits, bool $notNull, \Sentience\Database\Queries\Interfaces\Sql|int|float|null $default): static
AlterTableQuery->addString(string $name, int $size, bool $notNull, \Sentience\Database\Queries\Interfaces\Sql|string|null $default): static
AlterTableQuery->addDateTime(string $name, int $size, bool $notNull, \DateTimeInterface|Sentience\Database\Queries\Interfaces\Sql|null $default): static
```

# \Sentience\Database\Queries\DropTableQuery
```php
DropTableQuery->toSql(): string
DropTableQuery->execute(bool $emulatePrepare): \Sentience\Database\Results\ResultInterface
DropTableQuery->ifExists(): static
```

# \Sentience\Database\Result\ResultInterface
```php
ResultInterface->columns(): array
ResultInterface->scalar(?string $column = null): mixed
ResultInterface->fetchObject(string class, array $constructorArgs = []): ?object
ResultInterface->fetchObjects(string class, array $constructorArgs = []): array
ResultInterface->fetchAssoc(): ?array
ResultInterface->fetchAssocs(): array
```

# Initialize connection example
```php
$database Database::connect(
    driver: $driver,
    name: $name,
    socket: $socket,
    queries: $queries,
    options: $options,
    debug: $debug,
    usePDOAdapter: $usePDOAdapter
);
```

# Select query example
```php
$db->select(Query::subQuery($db->select('sub_table_1'), 'table1'))
    ->distinct()
    ->columns([
        'column1',
        Query::raw('CONCAT(column1, column2)'),
        'col2' => Query::raw('column2HERE')
    ])
    ->leftJoin(
        'leftjoin_table',
        fn(Join $join): Join => $join->on(
            ['leftjoin_table', 'join_column'],
            ['on_table', 'on_column']
        )
    )->innerJoin(
        'innerjoin_table',
        fn(Join $join): Join => $join->on(
            ['innerjoin_table', 'join_column'],
            ['on_table', 'on_column']
        )->whereBetween(['innerjoin_table', 'join_column'], 0, 9999)
    )->leftJoin(
        Query::subQuery(
            $db->select('sub_join_table'),
            'sjt'
        ),
        fn(Join $join): Join => $join->on(
            ['innerjoin_table', 'join_column'],
            ['on_table', 'on_column']
        )->whereBetween(['innerjoin_table', 'join_column'], 0, 9999)
    )
    ->join('RIGHT JOIN table2 jt ON jt.column1 = table1.column1 AND jt.column2 = table2.column2')
    ->whereEquals('column1', 10)
    ->whereGroup(
        fn($group) => $group
            ->whereGreaterThanOrEquals('column2', 20)
            ->orwhereIsNull('column3')
    )
    ->where('DATE(`created_at`) > :date OR DATE(`created_at`) < :date', [':date' => Query::now()])
    ->whereGroup(
        fn($group) => $group
            ->whereIn('column4', [1, 2, 3, 4])
            ->whereNotEquals('column5', 'test string')
    )
    ->whereGroup(fn($group) => $group)
    ->whereIn('column2', [])
    ->whereNotIn('column2', [])
    ->whereStartsWith('column2', 'a')
    ->whereEndsWith('column2', 'z')
    ->whereLike('column2', '%a%')
    ->whereNotLike('column2', '%z%')
    ->whereEmpty('empty_column')
    ->whereNotEmpty('not_empty_column')
    ->whereRegex('column6', 'file|read|write|open', 'i')
    ->whereNotRegex('column6', 'error')
    ->whereContains('column7', 'draft')
    ->whereOperator('columnOperator', '@>', 2)
    ->groupBy([
        ['table', 'column'],
        'column2',
        Query::raw('rawColumn')
    ])
    ->having('COUNT(*) > :count', [':count' => 10])
    ->orderByAsc('column4')
    ->orderByDesc('column5')
    ->orderByAsc(Query::raw('column6'))
    ->orderByDesc(Query::raw('column7'))
    ->limit(1)
    ->offset(10)
    ->union($db->select('union'))
    ->unionAll($db->select('union_all'))
```

# Create table with foreign key example
```php
$database$->createTable('table_1')
    ->ifNotExists()
    ->column('primary_key', 'int', true, null, true)
    ->column('column1', 'bigint', true)
    ->column('column2', 'varchar(255)')
    ->primaryKeys(['primary_key'])
    ->uniqueConstraint(['column1', 'column2'])
    ->foreignKeyConstraint('column1', 'table_2', 'reference_column', 'fk_table_1', [ReferentialActionEnum::ON_UPDATE_NO_ACTION])
    ->constraint('UNIQUE "test" COLUMNS ("column1", "column2")')
    ->toSql();
```

# Escaping
Sentience substitutes schema.table.column (example public.users.email) for arrays, like this `['public', 'users', 'email']`.