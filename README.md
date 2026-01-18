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

### Fully supported database dialects
- Firebird (PDO)
- MariaDB (PDO / Mysqli)
- MySQL (PDO / Mysqli)
- Postgres (PDO)
- SQLite (PDO / SQLite3)
- SQL Server (PDO)

### Partially supported database dialects
- Oracle OCI (PDO)

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
- LIKE / NOT LIKE case sensitive and insensitive (SQLITE uses LIKE converted to GLOB)
- Starts with / Ends with (using LIKE)
- Contains / Not contains (using LIKE)
- IN / NOT IN
- Less than / Less than or equals
- Greater than / Greater than or equals
- BETWEEN / NOT BETWEEN
- Empty / Not empty (Mimicking PHP's empty function)
- Regex / Not regex (SQLite also supported)
- EXISTS / NOT EXISTS (sub query)
- Group
- Operator
- Raw

### Alter table definitions
- Add column / Drop column
- Rename column
- Modify column (Not supported in SQLite)
- Add unique constraint (Not supported in SQLite)
- Add foreign key constraint (Not supported in SQLite)
- Drop constraint (Not supported in SQLite)

# Why Sentience as your database implementation

Ever used a database, and suddenly you realize that you're missing a functionality? With Sentience Database, you don't have to worry about that.
When a database doesn't support a feature, Sentience does its best to emulate this feature. Such examples are:

- MySQL gets support for returning (with ->lastInsertId('') set)
- SQLite gets support for regular expressions using REGEXP_LIKE
- Firebird, OCI, and SQLServer, get ON CONFLICT resolution support
- Firebird, OCI, and SQLServer, get IF EXISTS and IF NOT EXISTS support

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
// - 'oci'
// - 'pgsql'
// - 'sqlite'
// - 'sqlsrv'
```

Once the driver is initialized you can initialize a database instance using `::connect`, `::pdo`, or by passing in an `AdapterInterface` and `DialectInterface` manually.

```php
use Sentience\Database\Database;

$database = Database::connect(
    $driver,
    $name,
    $socket, // NetworkSocket, UnixSocket, or null for SQLite
    $queries, // A list of queries to execute when initializing the session
    $options, // An associative array of extra options (more on that in #4),
    $debug, // A callback that takes (string $query, float $startTime, ?string $error) as arguments
    $usePDOAdapter, // Use PDO for MariaDB/MySQL/SQLite connections instead of their native implementation
);
```

The socket constructor arg should be a `NetworkSocket` or `UnixSocket` class.

If you wish to build a custom database implementation, you can also manually initialize the database by calling the constructor with an `AdapterInterface` and a `DialectInterface`. Both have "abstract" classes you can extend to include a lot of functionality in your custom implementations directly. For adapters it's `AdapterAbstract`, and for dialects it's `SQLDialect` which implements the SQL 2016 standard to the best of its abilities.

# 2. Executing queries

With an initialized database object, you can start executing queries. The following methods are available:

```php
$database->exec(string $query): void;
$database->query(string $query): ResultInterface;
$database->prepared(string $query, array $params, bool $emulatePrepare = false): ResultInterface;
$database->queryWithParams(QueryWithParams $queryWithParams, bool $emulatePrepare = false): ResultInterface;
$database->beginTransaction(?string $name = null): void; // Supports nested transactions using savepoints
$database->commitTransaction(bool $releaseSavepoints = false, ?string $name = null): void; // Supports nested transactions using savepoints
$database->rollbackTransaction(bool $releaseSavepoints = false, ?string $name = null): void; // Supports nested transactions using savepoints
```

Some methods return a result. The results contain the following methods:

```php
$result->columns(): array // An associative array of columns ['column' => 'type']
$result->scalar(?string $column = null): mixed // $column null will return the first key in the associative array
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
        'leftjoin_table',
        fn (Join $join): Join => $join->on(
            ['leftjoin_table', 'join_column'],
            ['on_table', 'on_column']
        )
    )->innerJoin(
        'innerjoin_table',
        fn (Join $join): Join => $join->on(
            ['innerjoin_table', 'join_column'],
            ['on_table', 'on_column']
        )->whereBetween(['innerjoin_table', 'join_column'], 0, 9999)
    )
    ->join('RIGHT JOIN table2 jt ON jt.column1 = table1.column1 AND jt.column2 = table2.column2')
    ->whereEquals('column1', 10)
    ->whereGroup(
        fn(ConditionGroup $group): ConditionGroup => $group
            ->whereGreaterThanOrEquals('column2', 20)
            ->orwhereIsNull('column3')
    )
    ->where('DATE(`created_at`) > :date OR DATE(`created_at`) < :date', [':date' => Query::now()])
    ->whereGroup(
        fn(ConditionGroup $group): ConditionGroup => $group
            ->whereIn('column4', [1, 2, 3, 4])
            ->whereNotEquals('column5', 'test string')
    )
    ->whereGroup(fn(ConditionGroup $group): ConditionGroup => $group)
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
    ->where('created_at < ?', [Query::now()])
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
            ->whereRegex('regexable_column', '[0-9]', 'im')
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
    ->identity('id')
    ->float('column1')
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
public const string OPTIONS_PERSISTENT = 'persistent'; // bool
public const string OPTIONS_PDO_DSN = 'dsn'; // string
public const string OPTIONS_MYSQL_CHARSET = 'charset'; // string
public const string OPTIONS_MYSQL_COLLATION = 'collation'; // null|string
public const string OPTIONS_MYSQL_ENGINE = 'engine'; // string
public const string OPTIONS_PGSQL_SSL_MODE = 'ssl_mode'; // string
public const string OPTIONS_PGSQL_SSL_CERT = 'ssl_cert'; // string
public const string OPTIONS_PGSQL_SSL_KEY = 'ssl_key'; // string
public const string OPTIONS_PGSQL_SSL_ROOT_CERT = 'ssl_root_cert'; // string
public const string OPTIONS_PGSQL_SSL_CRL = 'ssl_crl'; // string
public const string OPTIONS_PGSQL_CLIENT_ENCODING = 'client_encoding'; // string
public const string OPTIONS_PGSQL_SEARCH_PATH = 'search_path'; // string
public const string OPTIONS_SQLITE_READ_ONLY = 'read_only'; // bool
public const string OPTIONS_SQLITE_ENCRYPTION_KEY = 'encryption_key'; // string
public const string OPTIONS_SQLITE_BUSY_TIMEOUT = 'busy_timeout'; // int in miliseconds
public const string OPTIONS_SQLITE_ENCODING = 'encoding'; // string
public const string OPTIONS_SQLITE_JOURNAL_MODE = 'journal_mode'; // string (https://sqlite.org/pragma.html#pragma_journal_mode)
public const string OPTIONS_SQLITE_FOREIGN_KEYS = 'foreign_keys'; // bool
public const string OPTIONS_SQLITE_OPTIMIZE = 'optimize'; // bool
public const string OPTIONS_SQLSRV_ENCRYPT = 'encrypt'; // bool
public const string OPTIONS_SQLSRV_TRUST_SERVER_CERTIFICATE = 'trust_server_certificate'; // bool
```

Most options work on a "if they're in the options array, they're applied".

# 5 Native upserts and emulated upserts (including RETURNING emulation)

MariaDB, MySQL, Postgres, and SQLite support some form of conflict resolution inside INSERT queries. MariaDB and MySQL support INSERT IGNORE and ON DUPLICATE KEY UPDATE, Postgres and SQLite support ON CONFLICT DO NOTHING and ON CONFLICT DO UPDATE.

For databases that do not support conflict resolution natively, Sentience emulates this process.

1. Perform select using on conflict columns as where conditions.
2. Count == 0 --> perform insert.
3. Count == 2 --> throw exception because the constraint is not unique.
4. Perform update using on conflict columns as where conditions.

For databases that do not support returning (MariaDB < 10.5, MySQL) another select is performed using the on conflict constraint or the last insert id, to retrieve the inserted or updated record.

If the dialect does not explicitly state that conflict resolution and returning are supported, it will use the fallback.

# 6 Stored procedures

If you want to re-use a query multiple times with a set list of parameters, you can create a stored procedure. To make them database intercompatible the procedures are stored inside the database class instead of the database.

To create a mutable stored procedure, you do the following:
```php
$database->createMutableStoredProcedure(
    'insert_movie'
    function (string $title, string $description) use ($database): Query {
        return $database->insert('movies')
            ->values([
                'title' => $title,
                'description' => $description
            ])
            ->onConflictIgnore(['title', 'description']);
    }
);
```

Which you can later call using the name:
```php
$movies = [
    ['title' => 'Star Wars', 'description' => 'Wars in the stars'],
    ['title' => 'Back To The Future', 'description' => 'A film about time travel']
];

foreach($movies as $movie) {
    $database->executeMutableStoredProcedure(
        'insert_movie',
        $movie
    );
}
```

If you wish to mutate the query, you can pass in an extra command:
```php
foreach($movies as $movie) {
    $database->executeMutableStoredProcedure(
        'insert_movie',
        $movie,
        function (InsertQuery $insertQuery) {
            $insertQuery->onConflictIgnore('movies_uniq')
                ->returning(['id']);
        }
    );
}
```

If you want the query to be immutable, you can do the following:
```php
$database->createImmutableStoredProcedure(
    'movies_cleanup',
    'DELETE FROM "movies" WHERE "published_at" < ?'
);

$result = $database->executeImmutableStoredProcedure(
    'movies_cleanup',
    [new DateTime('2025-01-01')]
);
```

# 7 Integration in your project

This database abstraction was created for the Sentience V3 framework, but will continue to evolve as its own package.

To get started, create a simple SQLite database:
```php
$database = Database::connect(
    Driver::from('sqlite'),
    'database.sqlite3'
);
```

From there, add options, initialization queries, and anything else your project requires.

If you wish to explore how this database is implemented, have a look at the parent project: [Sentience V3](https://github.com/Sentience-Framework/sentience-v3)

# 8 Database specific implementations

Sentience offers specific classes for each database implementation. They are located in the `src/Databases` folders. They contain extra functionality that differs too much per database to include in the regular query classes. Things like schema dumping.

These functionalities were not implemented in the abstract database class because they differ too much per database. Creating a consistent stream of information wasn't easy, or even possible.

Since these functionalities are likely only used when you know which specific database you're using, they were bundled with their database specific implementations.

# 9 Miscellaneous information about Sentience database

1. Both named and unnamed parameters are supported for query building. The `QueryWithParams` automatically converts named params to placeholders.
2. Mysqli does not officially support named params, so the `QueryWithParams` object automatically handles that for Mysqli.
3. PHP's SQLite3 Result objects have a bug that re-executes the query when calling `->fetchArray()`. PDO does not have this bug, so it is recommended to use the PDOAdapter for SQLite.
4. Emulated prepares are disabled by default, and can only be enabled on a query by query basis to prevent security issues.
5. Escaping columns with namespace works using arrays. `['public', 'users', 'email']` translates to `"public"."users"."email"`, or ``public`.`users`.`email`` for MySQL dialects.
6. Query::alias() can be used to create aliasses for your tables or columns, without having to resort to using raw statements.
7. Query::raw() offers a way to use raw unparameterized SQL in your queries.
8. Query::now() spawns a new DateTime object.
9. Empty IN or NOT IN lists compile to 1 <> 1 or 1 = 1.
10. Oracle OCI is implemented according to the available documentation online. Unlike the other databases which were tested in real world scenarios with Docker containers, Oracle OCI has not been tested.
11. Postgres is best supported, followed closely by MySQL (only for missing specific conflict handling)
