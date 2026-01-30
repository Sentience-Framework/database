<?php

namespace Sentience\Database\Adapters;

use Closure;
use PDO;
use PDOStatement;
use Throwable;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Driver;
use Sentience\Database\Exceptions\AdapterException;
use Sentience\Database\Exceptions\DriverException;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Results\PDOResult;
use Sentience\Database\Sockets\NetworkSocket;
use Sentience\Database\Sockets\SocketAbstract;

class PDOAdapter extends AdapterAbstract
{
    public const string PDO = 'PDO';

    protected PDO $pdo;

    public function __construct(
        Driver $driver,
        string $name,
        ?SocketAbstract $socket,
        array $queries,
        array $options,
        ?Closure $debug
    ) {
        if (!class_exists(static::PDO)) {
            throw new AdapterException('PDO extension is not installed');
        }

        parent::__construct(
            $driver,
            $name,
            $socket,
            $queries,
            $options,
            $debug
        );

        $dsn = $this->dsn(
            $driver,
            $name,
            $socket,
            $options
        );

        $this->pdo = new PDO(
            $dsn,
            $socket?->username,
            $socket?->password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $this->configurePDO($driver, $options);

        if (in_array($driver, [Driver::MARIADB, Driver::MYSQL])) {
            $this->configurePDOForMySQL($options);
        }

        if ($driver == Driver::PGSQL) {
            $this->configurePDOForPgSQL($options);
        }

        if ($driver == Driver::SQLITE) {
            $this->configurePDOForSQLite($options);
        }

        foreach ($queries as $query) {
            $this->exec($query);
        }
    }

    protected function dsn(
        Driver $driver,
        string $name,
        ?SocketAbstract $socket,
        array $options
    ): string {
        if (array_key_exists(static::OPTIONS_PDO_DSN, $options)) {
            return (string) $options[static::OPTIONS_PDO_DSN];
        }

        if ($driver == Driver::SQLITE) {
            return sprintf(
                '%s:%s',
                $driver->value,
                $name
            );
        }

        if (!$socket) {
            throw new DriverException('this driver requires a socket');
        }

        if ($driver == Driver::FIREBIRD) {
            return sprintf(
                '%s:dbname=%s/%d:%s',
                $driver->value,
                $socket->host,
                $socket->port,
                $name
            );
        }

        if ($driver == Driver::OCI) {
            return sprintf(
                '%s:dbname=//%s:%d/%s',
                $driver->value,
                $socket->host,
                $socket->port,
                $name
            );
        }

        if ($driver == Driver::SQLSRV) {
            return sprintf(
                '%s:Server=%s,%d;Database=%s;Encrypt=%s;TrustServerCertificate=%s',
                $driver->value,
                $socket->host,
                $socket->port,
                $name,
                ($options[static::OPTIONS_SQLSRV_ENCRYPT] ?? true) ? 'yes' : 'no',
                ($options[static::OPTIONS_SQLSRV_TRUST_SERVER_CERTIFICATE] ?? false) ? 'yes' : 'no'
            );
        }

        $build = fn (array $dsn): string => sprintf(
            '%s:%s',
            $driver == Driver::MARIADB ? Driver::MYSQL->value : $driver->value,
            implode(
                ';',
                array_map(
                    fn (null|int|float|string $value, string $key): string => sprintf(
                        '%s=%s',
                        $key,
                        (string) $value
                    ),
                    $dsn,
                    array_keys($dsn)
                )
            )
        );

        $dsn = ['dbname' => $name];

        if (in_array($driver, [Driver::MARIADB, Driver::MYSQL])) {
            $dsn = array_merge(
                $dsn,
                $socket instanceof NetworkSocket
                ? ['host' => $socket->host, 'port' => $socket->port]
                : ['unix_socket' => $socket->host]
            );

            return $build($dsn);
        }

        $dsn['host'] = $socket->host;
        $dsn['port'] = (int) $socket->port;

        if (array_key_exists(static::OPTIONS_PGSQL_SSL_MODE, $options)) {
            $dsn['sslmode'] = (string) $options[static::OPTIONS_PGSQL_SSL_MODE];
        }

        if (array_key_exists(static::OPTIONS_PGSQL_SSL_CERT, $options)) {
            $dsn['sslcert'] = (string) $options[static::OPTIONS_PGSQL_SSL_CERT];
        }

        if (array_key_exists(static::OPTIONS_PGSQL_SSL_KEY, $options)) {
            $dsn['sslkey'] = (string) $options[static::OPTIONS_PGSQL_SSL_KEY];
        }

        if (array_key_exists(static::OPTIONS_PGSQL_SSL_ROOT_CERT, $options)) {
            $dsn['sslrootcert'] = (string) $options[static::OPTIONS_PGSQL_SSL_ROOT_CERT];
        }

        if (array_key_exists(static::OPTIONS_PGSQL_SSL_CRL, $options)) {
            $dsn['sslcrl'] = (string) $options[static::OPTIONS_PGSQL_SSL_CRL];
        }

        if (array_key_exists(static::OPTIONS_PGSQL_CLIENT_ENCODING, $options)) {
            $dsn['options'] = sprintf(
                "'--client_encoding=%s'",
                (string) $options[static::OPTIONS_PGSQL_CLIENT_ENCODING]
            );
        }

        return $build($dsn);
    }

    protected function configurePDO(Driver $driver, array $options): void
    {
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);

        if (!in_array($driver, [Driver::SQLSRV])) {
            $this->pdo->setAttribute(PDO::ATTR_PERSISTENT, (bool) ($options[static::OPTIONS_PERSISTENT] ?? false));
        }
    }

    protected function configurePDOForMySQL(array $options): void
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

    protected function configurePDOForPgSQL(array $options): void
    {
        if (array_key_exists(static::OPTIONS_PGSQL_SEARCH_PATH, $options)) {
            $this->exec(
                sprintf(
                    "SET search_path TO %s",
                    (string) $options[static::OPTIONS_PGSQL_SEARCH_PATH]
                )
            );
        }
    }

    protected function configurePDOForSQLite(array $options): void
    {
        foreach (['sqliteCreateFunction', 'createFunction'] as $method) {
            if (method_exists($this->pdo, $method)) {
                [$this->pdo, $method](
                    static::REGEXP_FUNCTION,
                    fn (string $value, string $pattern): bool => $this->regexpFunction(
                        $value,
                        $pattern
                    ),
                    2
                );

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
            $this->exec('PRAGMA query_only = ON');
        }

        if (array_key_exists(static::OPTIONS_SQLITE_ENCRYPTION_KEY, $options)) {
            $this->exec(
                sprintf(
                    "PRAGMA key = '%s'",
                    (string) $options[static::OPTIONS_SQLITE_ENCRYPTION_KEY]
                )
            );
        }

        if (array_key_exists(static::OPTIONS_SQLITE_BUSY_TIMEOUT, $options)) {
            $this->exec(
                sprintf(
                    "PRAGMA busy_timeout = %d",
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
        return $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    public function exec(string $query): void
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

    public function query(string $query): PDOResult
    {
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
        $start = microtime(true);

        try {
            if ($emulatePrepare) {
                $this->enableEmulatePrepares();
            }

            $pdoStatement = $this->pdo->prepare($queryWithParams->query);
        } catch (Throwable $exception) {
            $this->debug(fn (): string => $queryWithParams->toSql($dialect), $start, $exception);

            throw $exception;
        } finally {
            if ($emulatePrepare) {
                $this->disableEmulatePrepares();
            }
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

            ctype_digit((string) $key)
                ? $this->bindValue($pdoStatement, $key, $value, $type)
                : $this->bindParam($pdoStatement, $key, $value, $type);
        }

        try {
            $pdoStatement->execute();
        } catch (Throwable $exception) {
            $this->debug(fn (): string => $queryWithParams->toSql($dialect), $start, $exception);

            throw $exception;
        } finally {
            if ($emulatePrepare) {
                $this->disableEmulatePrepares();
            }
        }

        $this->debug(fn (): string => $queryWithParams->toSql($dialect), $start);

        return new PDOResult($pdoStatement);
    }

    public function beginTransaction(DialectInterface $dialect, ?string $name = null): void
    {
        if ($this->inTransaction()) {
            return;
        }

        $this->pdo->beginTransaction();
    }

    public function commitTransaction(DialectInterface $dialect, ?string $name = null): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        $this->pdo->commit();
    }

    public function rollbackTransaction(DialectInterface $dialect, ?string $name = null): void
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

    public function lastInsertId(?string $name = null): null|int|string
    {
        try {
            $lastInsertId = $this->pdo->lastInsertId($name);
        } catch (Throwable $exception) {
            return null;
        }

        if (is_bool($lastInsertId)) {
            return null;
        }

        if (ctype_digit((string) $lastInsertId)) {
            return (int) $lastInsertId;
        }

        return $lastInsertId;
    }

    protected function enableEmulatePrepares(): void
    {
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    }

    protected function disableEmulatePrepares(): void
    {
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    protected function bindValue(PDOStatement $pdoStatement, int $key, null|bool|int|float|string $value, int $type): void
    {
        $pdoStatement->bindValue($key + 1, $value, $type);
    }

    protected function bindParam(PDOStatement $pdoStatement, string $key, null|bool|int|float|string $value, int $type): void
    {
        $pdoStatement->bindParam($key, $value, $type);
    }

    public function __destruct()
    {
        if ($this->driver == Driver::SQLITE) {
            $this->sqliteOptimize($this->options[static::OPTIONS_SQLITE_OPTIMIZE] ?? false);
        }
    }
}
