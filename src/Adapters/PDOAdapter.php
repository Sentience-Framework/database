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

class PDOAdapter extends AdapterAbstract
{
    protected ?PDO $pdo;

    public static function connect(
        Driver $driver,
        string $host,
        int $port,
        string $name,
        string $username,
        string $password,
        array $queries,
        array $options,
        ?Closure $debug
    ): static {
        return new static(
            function () use ($driver, $host, $port, $name, $username, $password, $options): PDO {
                $dsn = (function (Driver $driver, string $host, int $port, string $name, array $options): string{
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
            $debug
        );
    }

    public function __construct(
        Closure $connect,
        Driver $driver,
        array $queries,
        array $options,
        ?Closure $debug
    ) {
        parent::__construct(
            $connect,
            $driver,
            $queries,
            $options,
            $debug
        );

        $this->pdo = $connect();

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_PERSISTENT, $options[static::OPTIONS_PERSISTENT] ?? false);
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);

        if (in_array($driver, [Driver::MARIADB, Driver::MYSQL])) {
            $this->configurePdoForMySQL($options);
        }

        if ($driver == Driver::PGSQL) {
            $this->configurePdoForPgSQL($options);
        }

        if ($driver == Driver::SQLITE) {
            $this->configurePdoForSQLite($options);
        }

        foreach ($queries as $query) {
            $this->exec($query);
        }
    }

    protected function configurePdoForMySQL(array $options): void
    {
        if (array_key_exists(static::OPTIONS_MYSQL_CHARSET, $options)) {
            $this->mysqlNames(
                (string) $options[static::OPTIONS_MYSQL_CHARSET],
                $options[static::OPTIONS_MYSQL_COLLATION] ?? null
            );
        }

        if (array_key_exists(static::OPTIONS_MYSQL_ENGINE, $options)) {
            $this->mysqlEngine((string) $options[static::OPTIONS_MYSQL_ENGINE]);
        }
    }

    protected function configurePdoForPgSQL(array $options): void
    {
        if (array_key_exists(static::OPTIONS_PGSQL_SEARCH_PATH, $options)) {
            $this->exec(
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
            $this->exec('PRAGMA query_only = ON;');
        }

        if (array_key_exists(static::OPTIONS_SQLITE_ENCRYPTION_KEY, $options)) {
            $this->exec(
                sprintf(
                    "PRAGMA key = '%s';",
                    (string) $options[static::OPTIONS_SQLITE_ENCRYPTION_KEY]
                )
            );
        }

        if (array_key_exists(static::OPTIONS_SQLITE_BUSY_TIMEOUT, $options)) {
            $this->exec(
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

    public function version(): string
    {
        $this->throwExceptionIfDisconnected();

        return $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    public function exec(string $query): void
    {
        $this->throwExceptionIfDisconnected();

        $start = microtime(true);

        try {
            $this->pdo->exec($query);
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

            throw $exception;
        }

        $this->debug($query, $start);
    }

    public function query(string $query): PDOResult
    {
        $this->throwExceptionIfDisconnected();

        $start = microtime(true);

        try {
            $pdoStatement = $this->pdo->query($query);

            $this->debug($query, $start);

            return new PDOResult($pdoStatement);
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

            throw $exception;
        }
    }

    public function queryWithParams(DialectInterface $dialect, QueryWithParams $queryWithParams, bool $emulatePrepare): PDOResult
    {
        $this->throwExceptionIfDisconnected();

        $query = $queryWithParams->toSql($dialect);

        $start = microtime(true);

        try {
            if ($emulatePrepare) {
                $this->enableEmulatePrepares();
            }

            $pdoStatement = $this->pdo->prepare($queryWithParams->query);
        } catch (Throwable $exception) {
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
            $this->debug($query, $start, $exception);

            throw $exception;
        } finally {
            if ($emulatePrepare) {
                $this->disableEmulatePrepares();
            }
        }

        $this->debug($query, $start);

        return new PDOResult($pdoStatement);
    }

    public function beginTransaction(): void
    {
        $this->throwExceptionIfDisconnected();

        if ($this->inTransaction()) {
            return;
        }

        $this->pdo->beginTransaction();
    }

    public function commitTransaction(): void
    {
        $this->throwExceptionIfDisconnected();

        if (!$this->inTransaction()) {
            return;
        }

        $this->pdo->commit();
    }

    public function rollbackTransaction(): void
    {
        $this->throwExceptionIfDisconnected();

        if (!$this->inTransaction()) {
            return;
        }

        $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        $this->throwExceptionIfDisconnected();

        return $this->pdo->inTransaction();
    }

    public function lastInsertId(?string $name = null): null|int|string
    {
        $this->throwExceptionIfDisconnected();

        $lastInserId = $this->pdo->lastInsertId($name);

        if (is_bool($lastInserId)) {
            return null;
        }

        if (preg_match('/[0-9]+/', $lastInserId)) {
            return (int) $lastInserId;
        }

        return $lastInserId;
    }

    protected function enableEmulatePrepares(): void
    {
        $this->throwExceptionIfDisconnected();

        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    }

    protected function disableEmulatePrepares(): void
    {
        $this->throwExceptionIfDisconnected();

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

    public function disconnect(): void
    {
        if (!$this->isConnected()) {
            return;
        }

        if ($this->driver == Driver::SQLITE) {
            $this->sqliteOptimize($this->options[static::OPTIONS_SQLITE_OPTIMIZE] ?? false);
        }

        $this->pdo = null;
    }

    public function isConnected(): bool
    {
        return !is_null($this->pdo);
    }
}
