<?php

namespace Sentience\Database\Adapters;

use Closure;
use mysqli;
use Throwable;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Driver;
use Sentience\Database\Exceptions\DriverException;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Results\MySQLiResult;
use Sentience\Database\Results\Result;
use Sentience\Database\Sockets\NetworkSocket;
use Sentience\Database\Sockets\SocketAbstract;

class MySQLiAdapter extends AdapterAbstract
{
    public const string MYSQLI_NULL = 's';
    public const string MYSQLI_INT = 'i';
    public const string MYSQLI_FLOAT = 'd';
    public const string MYSQLI_STRING = 's';

    protected ?mysqli $mysqli = null;

    public static function fromSocket(
        Driver $driver,
        string $name,
        ?SocketAbstract $socket,
        array $queries,
        array $options,
        ?Closure $debug,
        bool $lazy = false
    ): static {
        if (!$socket) {
            throw new DriverException('this driver requires a socket');
        }

        $isNetworkSocket = $socket instanceof NetworkSocket;

        $hostname = $isNetworkSocket
            ? ($options[static::OPTIONS_PERSISTENT] ?? false) ? sprintf('p:%s', $socket->host) : $socket->host
            : null;

        return new static(
            fn (): mysqli => new mysqli(
                $hostname,
                $socket->username,
                $socket->password,
                $name,
                $isNetworkSocket ? $socket->port : null,
                !$isNetworkSocket ? $socket->host : null
            ),
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

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $this->mysqli = ($this->connect)();

        if (array_key_exists(static::OPTIONS_MYSQL_CHARSET, $this->options)) {
            $this->mysqlNames(
                function (string $query): void {
                    $this->mysqli->query($query);
                },
                (string) $this->options[static::OPTIONS_MYSQL_CHARSET],
                $options[static::OPTIONS_MYSQL_COLLATION] ?? null
            );
        }

        if (array_key_exists(static::OPTIONS_MYSQL_ENGINE, $this->options)) {
            $this->mysqlEngine(
                function (string $query): void {
                    $this->mysqli->query($query);
                },
                (string) $this->options[static::OPTIONS_MYSQL_ENGINE]
            );
        }

        foreach ($this->queries as $query) {
            $this->mysqli->query($query);
        }
    }

    public function disconnect(): void
    {
        if (!$this->isConnected()) {
            return;
        }

        $this->mysqli->close();

        $this->mysqli = null;
    }

    public function isConnected(): bool
    {
        return !is_null($this->mysqli);
    }

    public function ping(bool $reconnect = false): bool
    {
        if (!$this->isConnected()) {
            return false;
        }

        $isConnected = $this->mysqli->ping();

        if ($isConnected) {
            return true;
        }

        if (!$reconnect) {
            return false;
        }

        $this->reconnect();

        return true;
    }

    public function version(): int
    {
        $this->connect();

        $version = $this->mysqli->server_version;

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
            $this->mysqli->query($query);

            if ($this->lazy && $this->isInsertQuery($query)) {
                $this->cacheLastInsertId();
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

    public function query(string $query): MySQLiResult|Result
    {
        $this->connect();

        try {
            $start = microtime(true);

            $mysqliResult = $this->mysqli->query($query);

            $this->debug($query, $start);

            if ($this->lazy && $this->isInsertQuery($query)) {
                $this->cacheLastInsertId();
            }

            $result = new MySQLiResult($mysqliResult);

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

    public function queryWithParams(DialectInterface $dialect, QueryWithParams $queryWithParams, bool $emulatePrepare): MySQLiResult|Result
    {
        $this->connect();

        $queryWithParams->namedParamsToQuestionMarks();

        if ($emulatePrepare) {
            return $this->query($queryWithParams->toSql($dialect));
        }

        $start = microtime(true);

        try {
            $mysqliStmt = $this->mysqli->prepare($queryWithParams->query);
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
        }

        $paramTypes = [];
        $paramValues = [];

        foreach ($queryWithParams->params as $param) {
            $value = $dialect->castToDriver($param);

            $type = match (get_debug_type($value)) {
                'null' => static::MYSQLI_NULL,
                'int' => static::MYSQLI_INT,
                'float' => static::MYSQLI_FLOAT,
                'string' => static::MYSQLI_STRING,
                default => static::MYSQLI_STRING
            };

            $paramTypes[] = $type;
            $paramValues[] = $value;
        }

        if (count($paramValues) > 0) {
            $mysqliStmt->bind_param(
                implode(
                    '',
                    $paramTypes
                ),
                ...$paramValues
            );
        }

        try {
            $mysqliStmt->execute();
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
        }

        if ($this->lazy && $this->isInsertQuery($queryWithParams)) {
            $this->cacheLastInsertId();
        }

        $mysqliResult = $mysqliStmt->get_result();

        $this->debug($queryWithParams->toSql($dialect), $start);

        $result = new MySQLiResult($mysqliResult);

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

        $this->mysqli->begin_transaction(0, $name);

        $this->inTransaction = true;
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
            $this->mysqli->commit(0, $name);
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            $this->inTransaction = false;

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
            $this->mysqli->rollback(0, $name);
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            $this->inTransaction = false;

            if ($this->lazy) {
                $this->disconnect();
            }
        }
    }

    public function lastInsertId(?string $name = null): null|int|string
    {
        if ($this->lazy && $this->lastInsertId) {
            return $this->lastInsertId;
        }

        if (!$this->isConnected()) {
            return null;
        }

        $lastInsertId = $this->mysqli->insert_id;

        $this->lastInsertId = $this->lazy ? $lastInsertId : null;

        return $lastInsertId;
    }
}
