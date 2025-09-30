<?php

namespace Sentience\Database\Adapters;

use Closure;
use PDO;
use PDOStatement;
use Throwable;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Driver;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Results\PDOResult;

class PDOAdapter extends AdapterAbstract
{
    protected PDO $pdo;

    public function __construct(
        Driver $driver,
        string $host,
        int $port,
        string $name,
        string $username,
        string $password,
        array $queries,
        array $options,
        ?Closure $debug
    ) {
        parent::__construct(
            $driver,
            $host,
            $port,
            $name,
            $username,
            $password,
            $queries,
            $options,
            $debug
        );

        $dsn = $this->dsn(
            $driver,
            $host,
            $port,
            $name,
            $options
        );

        $this->pdo = new PDO(
            $dsn,
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
                PDO::ATTR_PERSISTENT => true
            ]
        );

        if ($driver == Driver::MYSQL) {
            $this->configurePDOForMySQL($options);
        }

        if ($driver == Driver::PGSQL) {
            $this->configurePDOForPgSQL($options);
        }

        if ($driver == Driver::SQLITE) {
            $this->configurePDOForSQLite($options);
        }

        foreach ($queries as $query) {
            $this->query($query);
        }
    }

    protected function dsn(
        Driver $driver,
        string $host,
        int $port,
        string $name,
        array $options
    ): string {
        if ($driver == Driver::SQLITE) {
            return sprintf(
                '%s:%s',
                $driver->value,
                $name
            );
        }

        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s',
            $driver->value,
            $host,
            $port,
            $name
        );

        if ($driver == Driver::MYSQL) {
            if (array_key_exists(static::OPTIONS_MYSQL_CHARSET, $options)) {
                $dsn .= sprintf(';charset=%s', (string) $options[static::OPTIONS_MYSQL_CHARSET]);
            }
        }

        if ($driver == Driver::PGSQL) {
            if (array_key_exists(static::OPTIONS_PGSQL_CLIENT_ENCODING, $options)) {
                $dsn .= sprintf(";options='--client_encoding=%s'", (string) $options[static::OPTIONS_PGSQL_CLIENT_ENCODING]);
            }
        }

        return $dsn;
    }

    protected function configurePDOForMySQL(array $options): void
    {
        if (array_key_exists(static::OPTIONS_MYSQL_ENGINE, $options)) {
            $this->mysqlEngine((string) $options[static::OPTIONS_MYSQL_ENGINE]);
        }
    }

    protected function configurePDOForPgSQL(array $options): void
    {
        if (array_key_exists(static::OPTIONS_PGSQL_SEARCH_PATH, $options)) {
            $this->query(
                sprintf(
                    "SET search_path TO %s;",
                    (string) $options[static::OPTIONS_PGSQL_SEARCH_PATH]
                )
            );
        }
    }

    protected function configurePDOForSQLite(array $options): void
    {
        if (method_exists($this->pdo, 'sqliteCreateFunction')) {
            $this->pdo->sqliteCreateFunction(
                static::REGEXP_FUNCTION,
                fn (string $pattern, string $value): bool => $this->regexpFunction($pattern, $value),
                static::REGEXP_FUNCTION_PARAMETER_COUNT
            );
        }

        if ($options[static::OPTIONS_SQLITE_READ_ONLY] ?? false) {
            $this->query('PRAGMA query_only = ON;');
        }

        if (array_key_exists(static::OPTIONS_SQLITE_ENCRYPTION_KEY, $options)) {
            $this->query(
                sprintf(
                    "PRAGMA key = '%s';",
                    (string) $options[static::OPTIONS_SQLITE_ENCRYPTION_KEY]
                )
            );
        }

        if (array_key_exists(static::OPTIONS_SQLITE_BUSY_TIMEOUT, $options)) {
            $this->query(
                sprintf(
                    "PRAGMA busy_timeout = %d;",
                    (int) $options[static::OPTIONS_SQLITE_BUSY_TIMEOUT]
                )
            );
        }

        if (array_key_exists(static::OPTIONS_SQLITE_ENCODING, $options)) {
            $this->sqliteEncoding((string) $options[static::OPTIONS_SQLITE_ENCODING]);
        }

        if (array_key_exists(static::OPTIONS_SQLITE_JOURNAL_MODE, $options)) {
            $this->sqliteJournalMode((string) $options[static::OPTIONS_SQLITE_JOURNAL_MODE]);
        }

        if (array_key_exists(static::OPTIONS_SQLITE_FOREIGN_KEYS, $options)) {
            $this->sqliteForeignKeys((bool) $options[static::OPTIONS_SQLITE_FOREIGN_KEYS]);
        }
    }

    public function query(string $query): void
    {
        $start = microtime(true);

        try {
            $this->pdo->exec($query);
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

            throw $exception;
        }

        $this->debug($query, $start);
    }

    public function queryWithParams(DialectInterface $dialect, QueryWithParams $queryWithParams): PDOResult
    {
        $query = $queryWithParams->toRawQuery($dialect);

        $start = microtime(true);

        try {
            $pdoStatement = $this->pdo->prepare($queryWithParams->query);
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

            throw $exception;
        }

        foreach ($queryWithParams->params as $key => $param) {
            $value = $dialect->castToDriver($param);

            $type = match (get_debug_type($value)) {
                'null' => PDO::PARAM_NULL,
                'bool' => PDO::PARAM_BOOL,
                'int' => PDO::PARAM_INT,
                'float' => PDO::PARAM_STR,
                'string' => PDO::PARAM_STR,
                default => PDO::PARAM_STR
            };

            is_numeric($key)
                ? $this->bindValue($pdoStatement, $key, $value, $type)
                : $this->bindParam($pdoStatement, $key, $value, $type);
        }

        try {
            $pdoStatement->execute();
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

            throw $exception;
        }

        $this->debug($query, $start);

        return new PDOResult($pdoStatement);
    }

    public function beginTransaction(): void
    {
        if ($this->inTransaction()) {
            return;
        }

        $this->pdo->beginTransaction();
    }

    public function commitTransaction(): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        $this->pdo->commit();
    }

    public function rollbackTransaction(): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function lastInsertId(?string $name = null): ?string
    {
        $lastInserId = $this->pdo->lastInsertId($name);

        if (is_bool($lastInserId)) {
            return null;
        }

        return $lastInserId;
    }

    protected function bindValue(PDOStatement $pdoStatement, int $key, mixed $value, int $type): void
    {
        $pdoStatement->bindParam($key + 1, $value, $type);
    }

    protected function bindParam(PDOStatement $pdoStatement, string $key, mixed $value, int $type): void
    {
        $pdoStatement->bindParam($key, $value, $type);
    }
}
