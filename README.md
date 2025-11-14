```
  ____             _   _                     
 / ___|  ___ _ __ | |_(_) ___ _ __   ___ ___ 
 \___ \ / _ \ '_ \| __| |/ _ \ '_ \ / __/ _ \
  ___| |  __/ | | | |_| |  __/ | | | |_|  __/
 |____/ \___|_| |_|\__|_|\___|_| |_|\___\___|

 Database

 The lightweight Database Abstraction For Fhe Sentience Framework

 By UniForceMusic                                             
```

The Sentience database abstraction offers a lightweight, no dependencies, database implementation. Through the use of adapter classes it can wrap around PDO, mysqli, and SQLite3. Through the use of easy to extend interfaces it's easy to add your adapters and dialects and use them natively in the Sentience database implementation.

### Natively supported database dialects
- Firebird
- MariaDB
- MySQL
- Postgres
- SQLite

### Unofficially supported through standard SQL dialect
- Cubrid
- IBM DB2
- Informix
- Oracle OCI
- SQL Server (dblib / ODBC / sqlsrv)

The goal of this database abstraction was to provide an interface that is universally supported across all the implemented database (with currently the only exception being table constraint altering in SQLite). This is achieved by adhering to the SQL standard as much as possible, with a few exception outside the standard such as RETURNING and REGEXP_LIKE. For databases that don't natively implement ON CONFLICT or RETURNING clauses, Sentience offers alternatives that emulate the feature.

## Sentience database features include:

### Queries
- SELECT
- INSERT (Native / emulated upsert support for every database)
- UPDATE
- DELETE
- CREATE TABLE
- ALTER TABLE
- DROP TABLE

### Where conditions
- Equals / Not equals
- IS NULL / IS NOT NULL
- LIKE / NOT LIKE
- Starts with / Ends with
- Contains / Not contains
- In / Not in
- Less than / Less than or equals
- Greater than / Greater than or equals
- Between / Not between
- Empty / Not empty (Mimicking PHP's empty function)
- Regex / Not regex (using preg pattern)
- Exists / Not exists (sub query)
- Group
- Raw

### Alter table definitions
- Add column / Drop column
- Rename column
- Modify column (Not supported in SQLite)
- Add unique constraint (Not supported in SQLite)
- Add foreign key constraint (Not supported in SQLite)
- Drop constraint (Not supported in SQLite)

# Why Sentience as your database implementation

Sentience wasn't made to be a drop-in replacement for Eloquent or Doctrine, rather, it attempts to borrow doctrines and best practices from Golang (mainly inspired by the simplicity of [Bun ORM](https://bun.uptrace.dev/)), but with the extra abstractions for where conditions, conflict resolutions, and joins.

# 1. Getting started

To initialize a Sentience database instance, start by initializing your driver from a string:
```php
<?php

use Sentience\Database\Driver;

$driver = Driver::from('pgsql');

// Available drivers:
// - 'firebird'
// - 'mariadb'
// - 'mysql'
// - 'pgsql'
// - 'sqlite'
// - 'cubrid' (Only SQL standard features + upsert)
// - 'ibm' (Only SQL standard features + upsert)
// - 'dblib' (Only SQL standard features + upsert)
// - 'informix' (Only SQL standard features + upsert)
// - 'odbc' (Only SQL standard features + upsert)
// - 'oci' (Only SQL standard features + upsert)
// - 'sqlsrv' (Only SQL standard features + upsert)
```

Once the driver is initialized you can initialize a database instance using `::connect`, `::pdo`, or by passing in an `AdapterInterface` and `DialectInterface` manually.

```php
use Sentience\Database\Database;

$database = Database::connect(
    $driver,
    $host,
    $port,
    $username,
    $password,
    $queries, // A list of queries to execute when initializing the session
    $options, // An associative array of extra options (more on that in #4),
    $debug, // A callback that takes (string $query, float $startTime, ?string $error) as arguments
    $usePdoAdapter, // Use PDO for MariaDB/MySQL/SQLite connections instead of their native implementation
    $lazy // Disconnect after each query, reconnect when ->exec(), ->query(), or ->queryWithParams() is called
);
```

If you wish the connect a custom PDO implementation, you can use `::pdo`. The first argument is a callback that should return a new instance of PDO. The reason it requires a callback, is because lazy mode uses this callback to reinitialize the connection after terminating it.

```php
use Sentience\Database\Database;

$database = Database::connect(
    function (): PDO {
        return new PDO($dsn, $username, $password, $options);
    },
    $driver,
    $queries, // A list of queries to execute when initializing the session
    $options, // An associative array of extra options (more on that in {INSERT CHAPTER}),
    $debug, // A callback that takes (string $query, float $startTime, ?string $error) as arguments
    $lazy // Disconnect after each query, reconnect when ->exec(), ->query(), or ->queryWithParams() is called
);
```

If you wish to build a custom database implementation, you can also manually initialize the database by calling the constructor with an `AdapterInterface` and a `DialectInterface`. Both have "abstract" classes you can extend to include a lot of functionality in your custom implementations directly. For adapters it's `AdapterAbstract`, and for dialects it's `SQLDialect` which implements the SQL 2016 standard to the best of its abilities.

# 2. Executing queries

With an initialized database object, you can start executing queries. The following methods are available:

```php
$database->exec(string $query): void;
$database->query(string $query): ResultInterface;
$database->prepared(string $query, array $params, bool $emulatePrepare = false): ResultInterface;
$database->queryWithParams(QueryWithParams $queryWithParams, bool $emulatePrepare = false): ResultInterface;
$database->beginTransaction(): void;
$database->commitTransaction(): void;
$database->rollbackTransaction(): void;
```

Some methods return a result. The results contain the following methods:

```php
$result->columns(): array // A numeric array of column names
$result->fetchObject(string $class, array $constructorArgs = []): ?object
$result->fetchObjects(string $class, array $constructorArgs = []): array
$result->fetchAssoc(): ?array
$result->fetchAssocs(): array
```

# 3 Building queries

Querybuilders are initialized from the database object. A query can be executed by calling `->execute()`. To get the raw SQL call `->toSql()`. Here are examples for each query:

## 3.1 Select

```php
$database->select(['public', 'table_1'], 'table1')
    ->distinct()
    ->columns([
        'column1',
        Query::raw('CONCAT(column1, column2)'),
        Query::alias(
            Query::raw('column2'),
            'col2'
        )
    ])
    ->leftJoin(
        Query::alias('table2', 'jt'),
        'joinColumn',
        ['public', 't1'],
        't1Column'
    )->innerJoin(
        ['public', 'table3'],
        'joinColumn',
        ['public', 'table1'],
        't1Column'
    )
    ->innerJoin(
        'table4',
        'joinColumn',
        'table1',
        't1Column'
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
    ->whereRegex('column6', '/file|read|write|open/i')
    ->whereNotRegex('column6', 'error')
    ->whereContains('column7', 'draft')
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
    ->execute();
```

## 3.2 Insert

```php
$database->insert('table_1')
    ->values([
        'column1' => Query::now(),
        'column2' => true,
        'column3' => false,
        'column4' => Query::raw('column1 + 1')
    ])
    ->onConflictUpdate(['id'], ['column_updated' => Query::now()]) // ON DUPLICATE KEY UPDATE / ON CONFLICT DO UPDATE
    ->onConflictIgnore(['id']) // INSERT IGNORE / ON CONFLICT DO NOTHING
    ->returning(['id'])
    ->lastInsertId('id') // Only required when using MariaDB (< 10.5), MySQL, and databases that don't support returning
    ->execute();
```

## 3.3 Update

```php
$database->update('table_1')
    ->values([
        'column1' => Query::now(),
        'column2' => true,
        'column3' => false,
        'column4' => Query::raw('column1 + 1')
    ])
    ->whereExists($db->select('sub_table_1')
        ->columns([
            'id',
            'name',
            'created_at',
            'updated_at'
        ])
        ->whereIn(
            'id',
            $db->select('sub_sub_table_1')
                ->columns(['id'])
                ->whereEquals('deleted_at', null)
        ))
    ->orWhereLessThanOrEquals(
        'count',
        $db->select('sub_table_2')
            ->columns([
                Query::raw('MAX(id)')
            ])
            ->whereBetween('column_5', 1, 2)
            ->whereRegex('regexable_column', '/[0-9]/im')
    )
    ->returning(['id'])
    ->execute();
```

## 3.4 Delete

```php
$database->delete('table_1')
    ->whereBetween('column2', 10, 20)
    ->orWhereNotBetween('column2', 70, 80)
    ->returning(['id'])
    ->execute();
```

## 3.5 Create table

```php
$database->createTable('table_1')
    ->ifNotExists()
    ->column('primary_key', 'int', true, null, true)
    ->column('column1', 'bigint', true)
    ->column('column2', 'varchar(255)')
    ->primaryKeys(['primary_key'])
    ->uniqueConstraint(['column1', 'column2'])
    ->foreignKeyConstraint('column1', 'table_2', 'reference_column', 'fk_table_1', [ReferentialActionEnum::ON_UPDATE_NO_ACTION])
    ->constraint('UNIQUE "test" COLUMNS ("column1", "column2")')
    ->execute();
```

## 3.6 Alter table

```php
$database->alterTable('table_1')
    ->addColumn('column3', 'INT')
    ->addColumn('columnDateTimeFunc', 'DATETIME', true, Query::raw('now()'))
    ->alterColumn('column3', 'TEXT AUTO_INCREMENT')
    ->renameColumn('column3', 'column4')
    ->dropColumn('column4')
    ->alter('ADD COLUMN id BIGINT REFERENCES table(id)')
    ->addPrimaryKeys(['pk']) // Not supported in SQLite
    ->addUniqueConstraint(['column1', 'column2'], 'unique_constraint') // Not supported in SQLite
    ->addForeignKeyConstraint('column4', 'reference_table', 'reference_column') // Not supported in SQLite
    ->dropConstraint('unique_constraint') // Not supported in SQLite
    ->execute();
```

## 3.7 Drop table

```php
$database->dropTable('table_1')
    ->ifExists()
    ->execute();
```

# 4 Adapter options

The `AdapterInterface` class holds the options as public constants.

```php
public const string OPTIONS_VERSION = 'version'; // null|int|string
public const string OPTIONS_PERSISTENT = 'persistent'; // bool
public const string OPTIONS_PDO_DSN = 'dsn'; // string
public const string OPTIONS_MYSQL_CHARSET = 'charset'; // string
public const string OPTIONS_MYSQL_COLLATION = 'collation'; // null|string
public const string OPTIONS_MYSQL_ENGINE = 'engine'; // string
public const string OPTIONS_PGSQL_CLIENT_ENCODING = 'client_encoding'; // string
public const string OPTIONS_PGSQL_SEARCH_PATH = 'search_path'; // string
public const string OPTIONS_SQLITE_READ_ONLY = 'read_only'; // bool
public const string OPTIONS_SQLITE_ENCRYPTION_KEY = 'encryption_key'; // string
public const string OPTIONS_SQLITE_BUSY_TIMEOUT = 'busy_timeout'; // int in miliseconds
public const string OPTIONS_SQLITE_ENCODING = 'encoding'; // string
public const string OPTIONS_SQLITE_JOURNAL_MODE = 'journal_mode'; // string (https://sqlite.org/pragma.html#pragma_journal_mode)
public const string OPTIONS_SQLITE_FOREIGN_KEYS = 'foreign_keys'; // bool
public const string OPTIONS_SQLITE_OPTIMIZE = 'optimize'; // bool
```

Most options work on a "if they're in the options array, they're applied".

# 5 Lazy mode

If you have a lot of processes running simultaneously, the amount of available database connection can become exhausted quickly. To prevent long running processes from hogging connections, Sentience allows you to automatically disconnect and reconnect, only keeping a connection active when one is required.

Having to disconnect and reconnect for every query adds extra latency. By calling `->disableLazy()` on database you can temporarely disable lazy mode, en re-enable it with `->enableLazy()`. The query result and last insert id are cached, so that when the connection is closed immediately after executing, you can still query the results.

When a transaction is started, lazy mode is temporarely disabled until the transaction is commited or rolled back.

# 6 Native upserts and emulated upserts (including RETURNING emulation)

MariaDB, MySQL, Postgres, and SQLite support some form of conflict resolution inside INSERT queries. MariaDB and MySQL support INSERT IGNORE and ON DUPLICATE KEY UPDATE, Postgres and SQLite support ON CONFLICT DO NOTHING and ON CONFLICT DO UPDATE.

For databases that do not support conflict resolution natively, Sentience emulates this process.

1. Perform select using on conflict columns as where conditions.
2. Count == 0 --> perform insert.
3. Count == 2 --> throw exception because the constraint is not unique.
4. Perform update using on conflict columns as where conditions.

For databases that do not support returning (MariaDB < 10.5, MySQL) another select is performed using the on conflict constraint or the last insert id, to retrieve the inserted or updated record.

If the dialect does not explicitly state that conflict resolution and returning are supported, it will use the fallback.

# 7 Integration in your project

This database abstraction was created for the Sentience V3 framework, but will continue to evolve as its own package. If you wish to explore how this database is implemented, have a look at the parent project: [Sentience V3](https://github.com/Sentience-Framework/sentience-v3)

# 8 Miscellaneous information about Sentience database

1. Both named and unnamed parameters are supported for query building. The `QueryWithParams` automatically converts named params to placeholders.
2. Mysqli does not officially support named params, so the `QueryWithParams` object automatically handles that for Mysqli.
3. PHP's SQLite3 Result objects have a bug that re-executes the query when calling `->fetchArray()`. PDO does not have this bug, so it is recommended to use the PDOAdapter for SQLite.
4. Emulated prepares are disabled by default, and can only be enabled on a query by query basis to prevent security issues.
