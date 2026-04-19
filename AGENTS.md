# Sentience Database - Agent Skill

## Overview

`sentience/database` is a PHP 8.3+ database abstraction library. It provides a fluent query builder with full support for SELECT, INSERT, UPDATE, DELETE, CREATE TABLE, ALTER TABLE, and DROP TABLE across MySQL, MariaDB, PostgreSQL, SQLite, Firebird, Oracle OCI, and SQL Server.

**Namespace:** `Sentience\Database`  
**Package name:** `sentience/database` (Composer)  
**PHP requirement:** ^8.3  
**Zero external runtime dependencies** — adapters wrap PDO, MySQLi, or SQLite3 extensions.

---

## Architecture (3 layers)

```
Database (entry point)
  -> Driver (enum: MySQL, PgSQL, SQLite, etc.)
       -> Adapter (PDO / MySQLi / SQLite3 — handles the actual connection & execution)
       -> Dialect (per-database — translates generic query objects to SQL, handles feature emulation)
  -> Query Builder (SelectQuery, InsertQuery, UpdateQuery, DeleteQuery, CreateTableQuery, AlterTableQuery, DropTableQuery, Table)
  -> Result (ResultInterface — fetchAssoc, fetchAssocs, scalar, fetchObject, columns)
```

### Key classes

| Class | Path | Purpose |
|---|---|---|
| `Database` | `src/Database.php` | Static factory: `Database::connect($driver, $name, ...)` |
| `Driver` (enum) | `src/Driver.php` | `FIREBIRD, MARIADB, MYSQL, OCI, PGSQL, SQLITE, SQLSRV` — each has `getAdapter()` and `getDialect()` |
| `DatabaseAbstract` | `src/Databases/DatabaseAbstract.php` | Base class implementing `DatabaseInterface` — holds `$adapter` and `$dialect` |
| `QueryWithParams` | `src/Queries/Objects/QueryWithParams.php` | Holds raw query string + params, converts to SQL via `toSql($dialect)` |

### Adapters

| Adapter | Drivers | Notes |
|---|---|---|
| `PDOAdapter` | Firebird, MySQL, MariaDB, OCI, PgSQL, SQLite, SQL Server | Default for most drivers |
| `MySQLiAdapter` | MySQL, MariaDB | Native MySQLi (not PDO) |
| `SQLite3Adapter` | SQLite | Native SQLite3 extension |

### Dialects

| Dialect | Covers |
|---|---|
| `DialectInterface` | Base interface with methods: `select()`, `insert()`, `update()`, `delete()`, `createTable()`, `alterTable()`, `dropTable()`, `type()` |
| `SQLDialect` | Fallback default |
| `MySQLDialect` | MySQL/MariaDB |
| `PgSQLDialect` | PostgreSQL |
| `SQLiteDialect` | SQLite |
| `FirebirdDialect` | Firebird |
| `OCIDialect` | Oracle |
| `SQLServerDialect` | SQL Server |

Each dialect knows how to generate SQL for its database and whether it natively supports `ON CONFLICT`, `RETURNING`, `IF EXISTS`, `IF NOT EXISTS`, etc. For unsupported features, the library emulates them (e.g., ON CONFLICT upsert for Firebird/OCI/SQL Server).

---

## Creating a Database instance

```php
use Sentience\Database\Database;
use Sentience\Database\Driver;

$driver = Driver::MYSQL;
$db = Database::connect($driver, 'dbname', $socket, $queries, $options, $debug, $usePDOAdapter);
```

Or with a custom adapter + dialect:
```php
$adapter = $driver->getAdapter($name, $socket, $queries, $options, $debug, false);
$dialect = $driver->getDialect($adapter->version());
$db = new Database($adapter, $dialect);
```

### Checking available drivers

```php
$available = Database::getAvailableDrivers(); // e.g. [Driver::MYSQL, Driver::PGSQL, Driver::SQLITE]
```

---

## Query Builders

All query builders extend `Query` (abstract) and implement `QueryInterface`.

### Common methods (all query builders)

| Method | Description |
|---|---|
| `toSql()` | Returns the SQL string (or array of strings for AlterTableQuery) |
| `toQueryWithParams()` | Returns `QueryWithParams` object |
| `execute($emulatePrepare = false)` | Executes and returns `ResultInterface` |
| `explain($emulatePrepare = false)` | Returns `EXPLAIN` results as array of assoc arrays |
| Static helpers | `Query::alias()`, `Query::expression()`, `Query::expressionf()`, `Query::identifier()`, `Query::param()`, `Query::raw()`, `Query::subQuery()`, `Query::currentTimestamp()`, `Query::now()`, `Query::escapeAnsi()`, `Query::escapeBackslash()` |

### 1. SELECT — `SelectQuery`

Obtained via `$db->select($table)` or `$db->selectTable($table, $alias)`.

#### Chaining methods

| Method | Signature |
|---|---|
| `columns(array $columns)` | Set columns. Assoc array: `alias => column`. Numeric keys = no alias. |
| `distinct($on = [])` | DISTINCT (or DISTINCT ON for PostgreSQL) |
| `from($table)` | Override table |
| `groupBy(array $columns)` | GROUP BY |
| `limit(int $limit)` | LIMIT (negative = null) |
| `offset(int $offset)` | OFFSET (zero or negative = null) |
| `orderByAsc($column)` | ORDER BY ASC ($column can be string, array for dotted, or Sql object) |
| `orderByDesc($column)` | ORDER BY DESC |
| `union(SelectQuery $q)` | UNION |
| `unionAll(SelectQuery $q)` | UNION ALL |
| `where*()` / `orWhere*()` | See Where conditions below |

#### Where conditions (prefix `where` for AND, `orWhere` for OR)

All chainable, return `$this`. Column can be `string|array` (dotted: `'table.column'`).

| Method | Example |
|---|---|
| `whereEquals($col, $val)` | `WHERE col = ?` |
| `whereNotEquals($col, $val)` | `WHERE col <> ?` |
| `whereIsNull($col)` | `WHERE col IS NULL` |
| `whereIsNotNull($col)` | `WHERE col IS NOT NULL` |
| `whereLike($col, $val, $caseInsensitive)` | `WHERE col LIKE ?` |
| `whereNotLike($col, $val, $caseInsensitive)` | |
| `whereStartsWith($col, $val)` | `WHERE col LIKE 'val%'` |
| `whereEndsWith($col, $val)` | `WHERE col LIKE '%val'` |
| `whereContains($col, $val)` | `WHERE col LIKE '%val%'` |
| `whereNotContains($col, $val)` | |
| `whereIn($col, $values)` | `WHERE col IN (...)` — $values can be array or SelectQuery subquery |
| `whereNotIn($col, $values)` | |
| `whereLessThan($col, $val)` | `WHERE col < ?` |
| `whereLessThanOrEquals($col, $val)` | |
| `whereGreaterThan($col, $val)` | `WHERE col > ?` |
| `whereGreaterThanOrEquals($col, $val)` | |
| `whereBetween($col, $min, $max)` | `WHERE col BETWEEN ? AND ?` |
| `whereNotBetween($col, $min, $max)` | |
| `whereEmpty($col)` | `WHERE col IS NULL OR col = 0 OR col = ''` |
| `whereNotEmpty($col)` | `WHERE col IS NOT NULL AND col <> 0 AND col <> ''` |
| `whereRegex($col, $pattern, $flags)` | `WHERE col REGEXP ?` |
| `whereNotRegex($col, $pattern, $flags)` | |
| `whereExists(SelectQuery $q)` | `WHERE EXISTS (subquery)` |
| `whereNotExists(SelectQuery $q)` | |
| `whereGroup(callable $cb)` | `WHERE (grouped conditions)` — callback receives `WhereGroup` |
| `whereOperator($col, $op, $val)` | Raw operator: `WHERE col <op> ?` |
| `wheref($format, ...$values)` | Format string: `WHERE CONCAT(col1, col2) = ?` |
| `where($sql, $values)` | Raw SQL: `WHERE col LIKE ?` |

Same methods with `or` prefix for OR chaining (e.g. `orWhereEquals`, `orHavingExists`).

#### Having conditions

Identical API to where, prefixed with `having` / `orHaving` (e.g. `havingEquals`, `orHavingIn`).

#### SelectQuery-specific extras

| Method | Returns |
|---|---|
| `count($emulatePrepare)` | Returns int count of rows |

### 2. INSERT — `InsertQuery`

Obtained via `$db->insert($table)`.

| Method | Description |
|---|---|
| `into($table)` | Set table |
| `values(array ...$values)` | Add one or more rows of values. Multiple calls = multiple rows. |
| `onConflictIgnore($conflict)` | ON CONFLICT ($conflict columns) DO NOTHING |
| `onConflictUpdate($conflict, $updates)` | ON CONFLICT ($conflict) DO UPDATE SET ... |
| `returning(array $columns)` | RETURNING clause (native or emulated) |
| `lastInsertId($column)` | Track last insert ID column for emulation |
| `emulateOnConflict($lastInsertId, $inTransaction)` | Emulate ON CONFLICT for dbs that don't support it |
| `emulateReturning($lastInsertId)` | Emulate RETURNING for dbs that don't support it |

**Caveat:** Emulated upsert only supports single-row inserts. Multiple values with `onConflict` will throw `QueryException`.

### 3. UPDATE — `UpdateQuery`

Obtained via `$db->update($table)`.

| Method | Description |
|---|---|
| `updates(array $updates)` | Key-value pairs to update: `['name' => 'new name']` |
| `returning(array $columns)` | RETURNING clause |
| `where*()` / `orWhere*()` | Same as SelectQuery |

### 4. DELETE — `DeleteQuery`

Obtained via `$db->delete($table)`.

| Method | Description |
|---|---|
| `from($table)` | Override table |
| `returning(array $columns)` | RETURNING clause |
| `where*()` / `orWhere*()` | Same as SelectQuery |

### 5. CREATE TABLE — `CreateTableQuery`

Obtained via `$db->createTable($table)`.

| Method | Description |
|---|---|
| `column($name, $type, $notNull, $default, $generatedByDefaultAsIdentity)` | Generic column |
| `autoIncrement($name, $bits, $addPrimaryKey)` | Identity column (alias for `identity()`) |
| `identity($name, $bits, $addPrimaryKey)` | Auto-increment with optional primary key |
| `bool($name, $notNull, $default)` | Boolean column |
| `int($name, $bits, $notNull, $default, $generatedByDefaultAsIdentity)` | Integer column |
| `float($name, $bits, $notNull, $default)` | Float/double column |
| `string($name, $size, $notNull, $default)` | String/varchar column |
| `text($name, $notNull, $default)` | Unlimited text |
| `dateTime($name, $size, $notNull, $default)` | DateTime column |
| `primaryKeys($columns)` | Set primary keys |
| `uniqueConstraint($columns, $name)` | Add unique constraint |
| `foreignKeyConstraint($column, $refTable, $refColumn, $name, $actions)` | Add FK constraint |
| `constraint($sql)` | Raw constraint SQL |
| `ifNotExists()` | Add IF NOT EXISTS |

### 6. ALTER TABLE — `AlterTableQuery`

Obtained via `$db->alterTable($table)`.

| Method | Description |
|---|---|
| `addColumn($name, $type, $notNull, $default, $generatedByDefaultAsIdentity)` | Add column |
| `alterColumn($column, $sql)` | Modify column (not supported in SQLite) |
| `renameColumn($old, $new)` | Rename column |
| `dropColumn($column)` | Drop column |
| `addPrimaryKeys($columns)` | Add primary keys |
| `addUniqueConstraint($columns, $name)` | Add unique constraint (not supported in SQLite) |
| `addForeignKeyConstraint($column, $refTable, $refColumn, $name, $actions)` | Add FK (not supported in SQLite) |
| `dropConstraint($constraint)` | Drop constraint (not supported in SQLite) |
| `alter($sql)` | Raw SQL alter statement |
| `addAutoIncrement($name, ...)` / `addIdentity($name, ...)` | Add identity column |
| `addBool()`, `addInt()`, `addFloat()`, `addString()`, `addText()`, `addDateTime()` | Type-specific add column |

**Important:** `AlterTableQuery::toSql()` returns `string[]` (array of SQL strings). `execute()` returns `ResultInterface[]`. One alter operation can produce multiple SQL statements.

### 7. DROP TABLE — `DropTableQuery`

Obtained via `$db->dropTable($table)`.

| Method | Description |
|---|---|
| `ifExists()` | Add IF EXISTS |

---

## `Table` helper class

Obtained via `$db->table($table)` — convenience wrapper that bundles common operations.

| Method | Description |
|---|---|
| `select($columns)` | SelectQuery with pre-set columns |
| `insert(...$values)` | InsertQuery with pre-set values |
| `update($values)` | UpdateQuery with pre-set updates |
| `delete()` | DeleteQuery |
| `create($callback)` | CreateTableQuery with optional callback |
| `createIfNotExists($callback)` | Same + IF NOT EXISTS |
| `alter($callback)` | AlterTableQuery with optional callback |
| `truncate()` | Deletes all rows |
| `drop()` / `dropIfExists()` | DropTableQuery |
| `columns()` | Returns column names of the table |
| `isEmpty()` | Returns bool |
| `copyFrom($from, $map, $ignoreExceptions)` | Copy rows from another table |
| `copyTo($to, $map, $ignoreExceptions)` | Copy rows to another table |
| `selectOrInsert($columns, $values)` | SELECT then INSERT if not found (upsert-like) |
| `insertOrIgnore($columns, $values)` | Alias for `selectOrInsert` |
| `insertOrUpdate($columns, $values)` | SELECT then UPDATE if found, INSERT if not |

---

## Database-level methods

| Method | Description |
|---|---|
| `exec($query)` | Execute raw SQL, no result |
| `query($query)` | Execute raw SQL, returns `ResultInterface` |
| `prepared($query, $params, $emulatePrepare)` | Execute with params, returns `ResultInterface` |
| `queryWithParams($queryWithParams, $emulatePrepare)` | Execute `QueryWithParams` object |
| `beginTransaction($name)` | Start transaction or savepoint |
| `commitTransaction($releaseSavepoints, $name)` | Commit |
| `rollbackTransaction($releaseSavepoints, $name)` | Rollback |
| `inTransaction()` | bool |
| `transaction($callback, ...)` | Wrap callable in transaction (auto-commit/rollback) |
| `lastInsertId($name)` | Get last insert ID |

---

## ResultInterface

| Method | Description |
|---|---|
| `columns()` | Returns column names as array |
| `scalar($column)` | Returns single scalar value |
| `fetchAssoc()` | Returns next row as assoc array (consumes row) |
| `fetchAssocs()` | Returns all remaining rows as assoc arrays |
| `fetchObject($class, $constructorArgs)` | Returns next row as object |
| `fetchObjects($class, $constructorArgs)` | Returns all remaining rows as objects |

---

## Sql objects (for embedding in queries)

| Object | Factory | Purpose |
|---|---|---|
| `Alias` | `Query::alias($identifier, $alias)` | Table/column alias |
| `SubQuery` | `Query::subQuery($selectQuery, $alias)` | Subquery with alias |
| `Param` | `Query::param($value)` | Placeholder parameter |
| `Expression` | `Query::expression($sql, $params)` | SQL expression with params |
| `ExpressionF` | `Query::expressionf($format, ...$values)` | Format string expression |
| `Identifier` | `Query::identifier($identifier)` | Quoted identifier |
| `Raw` | `Query::raw($sql)` | Raw SQL fragment |
| `CurrentTimestamp` | `Query::currentTimestamp()` | CURRENT_TIMESTAMP literal |
| `OrderBy` | Internal | Order by clause |
| `Join` | Internal | Join clause |
| `WhereGroup` | `whereGroup($cb)` | Grouped conditions |
| `HavingGroup` | `havingGroup($cb)` | Grouped having conditions |
| `Condition` | Internal | Single condition |
| `RawCondition` | `where($sql, $values)` | Raw where condition |
| `OnConflict` | `onConflictIgnore()` / `onConflictUpdate()` | ON CONFLICT clause |
| `Column` | Internal | Column definition |
| `ForeignKeyConstraint` | Internal | FK constraint |
| `UniqueConstraint` | Internal | Unique constraint |
| `AddColumn`, `DropColumn`, `RenameColumn`, `AlterColumn`, `AddPrimaryKeys`, `AddUniqueConstraint`, `AddForeignKeyConstraint`, `DropConstraint` | Internal | ALTER TABLE operations |
| `Addition` / `Subtraction` etc. | `ExpressionF` format | SQL expressions via format string |
| `QueryWithParams` | `new QueryWithParams($sql, $params)` | Query + params container |

---

## Enums

| Enum | Values |
|---|---|
| `Driver` | `FIREBIRD, MARIADB, MYSQL, OCI, PGSQL, SQLITE, SQLSRV` |
| `TypeEnum` | `BOOL, INT, FLOAT, STRING, DATETIME` |
| `JoinEnum` | `LEFT_JOIN, INNER_JOIN` |
| `UnionEnum` | `UNION, UNION_ALL` |
| `ChainEnum` | `AND, OR` |
| `ConditionEnum` | `EQUALS, NOT_EQUALS, IS_NULL, IS_NOT_NULL, LIKE, NOT_LIKE, IN, NOT_IN, LESS_THAN, LESS_THAN_OR_EQUALS, GREATER_THAN, GREATER_THAN_OR_EQUALS, BETWEEN, NOT_BETWEEN, EMPTY, NOT_EMPTY, REGEX, NOT_REGEX, EXISTS, NOT_EXISTS, RAW` |
| `OrderByDirectionEnum` | `ASC, DESC` |
| `ReferentialActionEnum` | For FK actions (ON UPDATE / ON DELETE) |

---

## Exceptions

All extend `Exception` in `Sentience\Database\Exceptions`:

| Exception | When |
|---|---|
| `DatabaseException` | General database errors |
| `DriverException` | Driver-related errors |
| `AdapterException` | Adapter-related errors |
| `QueryException` | Query construction/execution errors (e.g. emulated upsert with multiple rows) |
| `QueryWithParamsException` | Param substitution errors (e.g. missing named param) |
| `MacroException` | Macro-related errors |

---

## Caveats & Important Notes

1. **`AlterTableQuery::toSql()` returns `string[]`** — not a single string. Each alter operation can produce multiple SQL statements. Same for `execute()` which returns `ResultInterface[]`.

2. **Emulated ON CONFLICT** only works with single-row inserts. Calling `onConflict*()` with multiple `values()` will throw `QueryException`.

3. **Emulated RETURNING** requires setting `->lastInsertId($column)` so the library knows which column to filter on after insert.

4. **SQLite limitations:** No native support for modifying columns, adding unique constraints, adding FK constraints, or dropping constraints. These are not supported (no emulation).

5. **`whereEmpty()` / `whereNotEmpty()`** mimic PHP's `empty()` function: checks for NULL, 0, and `''`.

6. **`like()` escaping** — `%`, `_`, `-`, `^`, `[`, `]` are auto-escaped in LIKE patterns. Use `$escapeBackslash` parameter to also escape `\`.

7. **`whereGroup()` / `havingGroup()`** use reflection to detect the type hint of the callback's first parameter. If the hint is `ConditionGroup`, defaults to generic `WhereGroup`. For typed hints like `WhereGroup` or `HavingGroup`, uses that specific class.

8. **`QueryWithParams::toSql()`** handles named params (`:name`) and question mark params (`?`) separately. Named params are replaced by name; question marks are replaced positionally.

9. **`QueryWithParams` PCRE ini settings** — temporarily modifies `pcre.jit`, `pcre.backtrack_limit`, and `pcre.recursion_limit` during regex substitution. Restored in `finally` block.

10. **`distinct()`** with no args = `DISTINCT`. With array arg = `DISTINCT ON (col1, col2)` (PostgreSQL-specific).

11. **`offset()`** — zero or negative values are treated as null (no OFFSET).

12. **`limit()`** — negative values are treated as null (no LIMIT).

13. **Transaction savepoints** — nested transactions use savepoints. `$releaseSavepoints = true` commits/rolls back the outermost transaction and clears all savepoints.

14. **`Table::isEmpty()`** returns `true` when table has rows (inverted logic — name is `isEmpty` but returns `count > 0`). Double-check: actually returns `count > 0` which means "is NOT empty". The method name is misleading — it returns `true` if the table is NOT empty.

15. **`Table::copyFrom()` / `copyTo()`** — row-by-row copy. For large datasets this is slow. The `$map` callback receives each row and should return the filtered/mapped row. Null values are removed from the row.

16. **`Table::insertOrUpdate()`** — does a SELECT first to check existence, then UPDATE or INSERT. Not atomic — there's a race condition between the SELECT and INSERT/UPDATE.

17. **Column identifiers** — can be `string` (plain name), `array` (dotted: `['table', 'column']`), or any `Sql` object (`Identifier`, `Expression`, etc.).

18. **`expressionf()` format strings** — use PHP `sprintf`-style placeholders: `%s`, `%d`, `%f`, `%b` (boolean), `%r` (raw SQL), `%n` (current timestamp), `%i` (identifier). Values are passed as variadic args.

---

## Quick patterns

### Simple select
```php
$db->select('users')
    ->columns(['id', 'name'])
    ->whereEquals('active', true)
    ->orderByDesc('created_at')
    ->limit(10)
    ->execute();
```

### Select with join
```php
$db->select('users')
    ->leftJoin('posts', fn(Join $j) => $j->whereOperator(['users.id'], '=', ['posts.user_id']))
    ->columns(['users.*', 'posts.title'])
    ->execute();
```

### Insert with on conflict
```php
$db->insert('users')
    ->values(['name' => 'John', 'email' => 'john@example.com'])
    ->onConflictUpdate(['email'], ['name' => ExpressionF::update()])
    ->returning()
    ->execute();
```

### Create table
```php
$db->createTable('users')
    ->autoIncrement('id')
    ->string('name', 255, true)
    ->string('email', 255, true)
    ->dateTime('created_at', 6, false, Query::currentTimestamp())
    ->uniqueConstraint(['email'])
    ->execute();
```

### Transaction
```php
$db->transaction(function (DatabaseInterface $db) {
    $db->insert('users')->values(['name' => 'John'])->execute();
    $db->insert('posts')->values(['user_id' => $db->lastInsertId('id'), 'title' => 'Hello'])->execute();
});
```

### Table helper
```php
$db->table('users')
    ->insertOrIgnore(['email' => 'john@example.com'], ['name' => 'John'])
    ->execute();
```

### Raw query with params
```php
$db->prepared(
    'SELECT * FROM users WHERE name LIKE ? AND age > ?',
    ['%John%', 18]
);
```

### Using Sql objects
```php
$db->select(Query::alias('users', 'u'))
    ->columns([
        'id' => Query::identifier(['u', 'id']),
        'name' => Query::expressionf('%s || %s', Query::identifier(['u', 'first_name']), Query::identifier(['u', 'last_name'])),
    ])
    ->where(Query::raw('age > ?'), [18])
    ->execute();
```

### Subquery in WHERE
```php
$sub = $db->select('orders')->whereGreaterThan('total', 100);
$db->select('users')
    ->whereIn('id', $sub)
    ->execute();
```

### Union
```php
$sel1 = $db->select('users')->columns(['name', 'email']);
$sel2 = $db->select('customers')->columns(['name', 'email']);
$sel1->unionAll($sel2)->execute();
```
