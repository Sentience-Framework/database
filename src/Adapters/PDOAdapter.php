<?php

namespace Sentience\Database\Adapters;

use Closure;
use PDO;
use PDOStatement;
use Throwable;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Driver;
use Sentience\Database\Exceptions\DriverException;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Results\PDOResult;
use Sentience\Database\Results\Result;

class PDOAdapter extends AdapterAbstract
{
    protected ?PDO $pdo = null;

    public static function pdo(
        Driver $driver,
        string $host,
        int $port,
        string $name,
        string $username,
        string $password,
        array $queries,
        array $options,
        ?Closure $debug,
        bool $lazy = false
    ): static {
        return new static(
            function () use ($driver, $host, $port, $name, $username, $password, $options): PDO {
                $dsn = (function (Driver $driver, string $host, int $port, string $name, array $options): string {
                    if (array_key_exists(static::OPTIONS_PDO_DSN, $options)) {
                        return (string) $options[static::OPTIONS_PDO_DSN];
                    }

                    if (!in_array($driver, [Driver::MARIADB, Driver::MYSQL, Driver::PGSQL, Driver::SQLITE])) {
                        throw new DriverException('this driver requires a dsn');
                    }

                    if ($driver == Driver::SQLITE) {
                        return sprintf(
                            '%s:%s',
                            $driver->value,
                            $name
                        );
                    }

                    $dsn = sprintf(
                        '%s:host=%s;port=%s;dbname=%s',
                        $driver == Driver::MARIADB ? Driver::MYSQL->value : $driver->value,
                        $host,
                        $port,
                        $name
                    );

                    if ($driver == Driver::PGSQL) {
                        if (array_key_exists(static::OPTIONS_PGSQL_CLIENT_ENCODING, $options)) {
                            $dsn .= sprintf(
                                ";options='--client_encoding=%s'",
                                (string) $options[static::OPTIONS_PGSQL_CLIENT_ENCODING]
                            );
                        }
                    }

                    return $dsn;
                })($driver, $host, $port, $name, $options);

                return new PDO(
                    $dsn,
                    $username,
                    $password
                );
            },
            $driver,
            $queries,
            $options,
            $debug,
            $lazy
        );
    }

    public function connect(): void
    {
        if ($this->isConnected()) {
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
        if (!$this->isConnected()) {
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

        parent::disconnect();
    }

    public function isConnected(): bool
    {
        return !is_null($this->pdo);
    }

    public function ping(bool $reconnect = false): bool
    {
        if (!$this->isConnected()) {
            return false;
        }

        try {
            $this->pdo->exec('SELECT 1');

            return true;
        } catch (Throwable $exception) {
            if ($reconnect) {
                $this->reconnect();

                return true;
            }

            return false;
        }
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
                    fn (string $value, string $pattern, string $flags = ''): bool => $this->regexpLikeFunction(
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

        if ($this->lazy && !$this->inTransaction()) {
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

            if ($this->lazy && $this->isInsertQuery($query)) {
                $this->lastInsertId();
            }
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

            throw $exception;
        } finally {
            if ($this->lazy && !$this->inTransaction()) {
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

            if ($this->lazy && $this->isInsertQuery($query)) {
                $this->lastInsertId();
            }

            $result = new PDOResult($pdoStatement);

            return $this->lazy
                ? Result::fromInterface($result)
                : $result;
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

            throw $exception;
        } finally {
            if ($this->lazy && !$this->inTransaction()) {
                $this->disconnect();
            }
        }
    }

    public function queryWithParams(DialectInterface $dialect, QueryWithParams $queryWithParams, bool $emulatePrepare): PDOResult|Result
    {
        $this->connect();

        $start = microtime(true);

        try {
            if ($emulatePrepare) {
                $this->enableEmulatePrepares();
            }

            $pdoStatement = $this->pdo->prepare($queryWithParams->query);
        } catch (Throwable $exception) {
            if ($this->lazy && !$this->inTransaction()) {
                $this->disconnect();
            }

            if ($emulatePrepare) {
                $this->disableEmulatePrepares();
            }

            $this->debug(
                $queryWithParams->toSql($dialect),
                $start,
                $exception
            );

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
            if ($this->lazy && !$this->inTransaction()) {
                $this->disconnect();
            }

            $this->debug(
                $queryWithParams->toSql($dialect),
                $start,
                $exception
            );

            throw $exception;
        } finally {
            if ($emulatePrepare) {
                $this->disableEmulatePrepares();
            }
        }

        $this->debug($queryWithParams->toSql($dialect), $start);

        if ($this->lazy && $this->isInsertQuery($queryWithParams)) {
            $this->lastInsertId();
        }

        $result = new PDOResult($pdoStatement);

        if ($this->lazy && !$this->inTransaction()) {
            $result = Result::fromInterface($result);

            $this->disconnect();
        }

        return $result;
    }

    public function beginTransaction(DialectInterface $dialect, ?string $name = null): void
    {
        $this->connect();

        if ($this->inTransaction()) {
            return;
        }

        $this->pdo->beginTransaction();
    }

    public function commitTransaction(DialectInterface $dialect, ?string $name = null): void
    {
        if (!$this->isConnected()) {
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

    public function rollbackTransaction(DialectInterface $dialect, ?string $name = null): void
    {
        if (!$this->isConnected()) {
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
        if (!$this->isConnected()) {
            return false;
        }

        return $this->pdo->inTransaction();
    }

    public function lastInsertId(?string $name = null): null|int|string
    {
        if ($this->lazy && $this->lastInsertId) {
            return $this->lastInsertId;
        }

        if (!$this->isConnected()) {
            return null;
        }

        $lastInsertId = $this->pdo->lastInsertId($name);

        $this->lastInsertId = $this->lazy ? $lastInsertId : null;

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
        if (!$this->isConnected()) {
            return;
        }

        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    }

    protected function disableEmulatePrepares(): void
    {
        if (!$this->isConnected()) {
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
