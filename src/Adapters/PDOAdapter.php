<?php

namespace Sentience\Database\Adapters;

use PDO;
use PDOStatement;
use Throwable;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Driver;
use Sentience\Database\Exceptions\AdapterException;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Results\PDOResult;
use Sentience\Database\Results\Result;

class PDOAdapter extends AdapterAbstract
{
    protected ?PDO $pdo = null;

    public function connect(): void
    {
        if ($this->connected()) {
            return;
        }

        $this->pdo = ($this->connect)();

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_PERSISTENT, $this->options[static::OPTIONS_PERSISTENT] ?? false);
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);

        if (in_array($this->driver, [Driver::MARIADB, Driver::MYSQL])) {
            $this->configurePdoForMySQL($this->options);
        }

        if ($this->driver == Driver::PGSQL) {
            $this->configurePdoForPgSQL($this->options);
        }

        if ($this->driver == Driver::SQLITE) {
            $this->configurePdoForSQLite($this->options);
        }

        foreach ($this->queries as $query) {
            $this->pdo->exec($query);
        }
    }

    public function disconnect(): void
    {
        if (!$this->connected()) {
            return;
        }

        if ($this->driver == Driver::SQLITE) {
            $this->sqliteOptimize(
                function (string $query): void {
                    $this->pdo->exec($query);
                },
                $this->options[static::OPTIONS_SQLITE_OPTIMIZE] ?? false
            );
        }

        $this->pdo = null;
    }

    public function connected(): bool
    {
        return !is_null($this->pdo);
    }

    protected function configurePdoForMySQL(array $options): void
    {
        if (array_key_exists(static::OPTIONS_MYSQL_CHARSET, $options)) {
            $this->mysqlNames(
                function (string $query): void {
                    $this->pdo->exec($query);
                },
                (string) $options[static::OPTIONS_MYSQL_CHARSET],
                $options[static::OPTIONS_MYSQL_COLLATION] ?? null
            );
        }

        if (array_key_exists(static::OPTIONS_MYSQL_ENGINE, $options)) {
            $this->mysqlEngine(
                function (string $query): void {
                    $this->pdo->exec($query);
                },
                (string) $options[static::OPTIONS_MYSQL_ENGINE]
            );
        }
    }

    protected function configurePdoForPgSQL(array $options): void
    {
        if (array_key_exists(static::OPTIONS_PGSQL_SEARCH_PATH, $options)) {
            $this->pdo->exec(
                sprintf(
                    "SET search_path TO %s;",
                    (string) $options[static::OPTIONS_PGSQL_SEARCH_PATH]
                )
            );
        }
    }

    protected function configurePdoForSQLite(array $options): void
    {
        foreach (['sqliteCreateFunction', 'createFunction'] as $method) {
            if (method_exists($this->pdo, $method)) {
                [$this->pdo, $method](
                    static::REGEXP_LIKE_FUNCTION,
                    fn(string $value, string $pattern, string $flags = ''): bool => $this->regexpLikeFunction(
                        $value,
                        $pattern,
                        $flags
                    )
                );
            }
        }

        if ($options[static::OPTIONS_SQLITE_READ_ONLY] ?? false) {
            $this->pdo->exec('PRAGMA query_only = ON;');
        }

        if (array_key_exists(static::OPTIONS_SQLITE_ENCRYPTION_KEY, $options)) {
            $this->pdo->exec(
                sprintf(
                    "PRAGMA key = '%s';",
                    (string) $options[static::OPTIONS_SQLITE_ENCRYPTION_KEY]
                )
            );
        }

        if (array_key_exists(static::OPTIONS_SQLITE_BUSY_TIMEOUT, $options)) {
            $this->pdo->exec(
                sprintf(
                    "PRAGMA busy_timeout = %d;",
                    (int) $options[static::OPTIONS_SQLITE_BUSY_TIMEOUT]
                )
            );
        }

        if (array_key_exists(static::OPTIONS_SQLITE_ENCODING, $options)) {
            $this->sqliteEncoding(
                function (string $query): void {
                    $this->pdo->exec($query);
                },
                (string) $options[static::OPTIONS_SQLITE_ENCODING]
            );
        }

        if (array_key_exists(static::OPTIONS_SQLITE_JOURNAL_MODE, $options)) {
            $this->sqliteJournalMode(
                function (string $query): void {
                    $this->pdo->exec($query);
                },
                (string) $options[static::OPTIONS_SQLITE_JOURNAL_MODE]
            );
        }

        if (array_key_exists(static::OPTIONS_SQLITE_FOREIGN_KEYS, $options)) {
            $this->sqliteForeignKeys(
                function (string $query): void {
                    $this->pdo->exec($query);
                },
                (bool) $options[static::OPTIONS_SQLITE_FOREIGN_KEYS]
            );
        }
    }

    public function version(): string
    {
        $this->connect();

        $version = $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);

        if ($this->lazy) {
            $this->disconnect();
        }

        return $version;
    }

    public function exec(string $query): void
    {
        $this->connect();

        $start = microtime(true);

        try {
            $this->pdo->exec($query);
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

            throw $exception;
        } finally {
            if ($this->lazy) {
                $this->disconnect();
            }
        }

        $this->debug($query, $start);
    }

    public function query(string $query): PDOResult|Result
    {
        $this->connect();

        $start = microtime(true);

        try {
            $pdoStatement = $this->pdo->query($query);

            $this->debug($query, $start);

            $result = new PDOResult($pdoStatement);

            return $this->lazy
                ? Result::fromInterface($result)
                : $result;
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

            throw $exception;
        } finally {
            if ($this->lazy) {
                $this->disconnect();
            }
        }
    }

    public function queryWithParams(DialectInterface $dialect, QueryWithParams $queryWithParams, bool $emulatePrepare): PDOResult|Result
    {
        $this->connect();

        $query = $queryWithParams->toSql($dialect);

        $start = microtime(true);

        try {
            if ($emulatePrepare) {
                $this->enableEmulatePrepares();
            }

            $pdoStatement = $this->pdo->prepare($queryWithParams->query);
        } catch (Throwable $exception) {
            if ($this->lazy) {
                $this->disconnect();
            }

            if ($emulatePrepare) {
                $this->disableEmulatePrepares();
            }

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
            if ($this->lazy) {
                $this->disconnect();
            }

            $this->debug($query, $start, $exception);

            throw $exception;
        } finally {
            if ($emulatePrepare) {
                $this->disableEmulatePrepares();
            }
        }

        $this->debug($query, $start);

        $result = new PDOResult($pdoStatement);

        if ($this->lazy) {
            $result = Result::fromInterface($result);

            $this->disconnect();
        }

        return $result;
    }

    public function beginTransaction(): void
    {
        $this->connect();

        if ($this->inTransaction()) {
            return;
        }

        $this->pdo->beginTransaction();
    }

    public function commitTransaction(): void
    {
        if (!$this->connected()) {
            return;
        }

        if (!$this->inTransaction()) {
            return;
        }

        try {
            $this->pdo->commit();
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            if ($this->lazy) {
                $this->disconnect();
            }
        }
    }

    public function rollbackTransaction(): void
    {
        if (!$this->connected()) {
            return;
        }

        if (!$this->inTransaction()) {
            return;
        }

        try {
            $this->pdo->rollBack();
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            if ($this->lazy) {
                $this->disconnect();
            }
        }
    }

    public function inTransaction(): bool
    {
        if (!$this->connected()) {
            return false;
        }

        return $this->pdo->inTransaction();
    }

    public function lastInsertId(?string $name = null): null|int|string
    {
        if ($this->lazy) {
            throw new AdapterException('last insert id is not support in lazy mode');
        }

        if (!$this->connected()) {
            return null;
        }

        $lastInsertId = $this->pdo->lastInsertId($name);

        if (is_bool($lastInsertId)) {
            return null;
        }

        if (preg_match('/[0-9]+/', $lastInsertId)) {
            return (int) $lastInsertId;
        }

        return $lastInsertId;
    }

    protected function enableEmulatePrepares(): void
    {
        if (!$this->connected()) {
            return;
        }

        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    }

    protected function disableEmulatePrepares(): void
    {
        if (!$this->connected()) {
            return;
        }

        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
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
