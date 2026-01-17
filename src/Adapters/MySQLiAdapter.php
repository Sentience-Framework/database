<?php

namespace Sentience\Database\Adapters;

use Closure;
use mysqli;
use Throwable;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Driver;
use Sentience\Database\Exceptions\AdapterException;
use Sentience\Database\Exceptions\DriverException;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Results\MySQLiResult;
use Sentience\Database\Sockets\NetworkSocket;
use Sentience\Database\Sockets\SocketAbstract;

class MySQLiAdapter extends AdapterAbstract
{
    public const string MYSQLI_NULL = 's';
    public const string MYSQLI_INT = 'i';
    public const string MYSQLI_FLOAT = 'd';
    public const string MYSQLI_STRING = 's';

    protected mysqli $mysqli;

    public function __construct(
        Driver $driver,
        string $name,
        ?SocketAbstract $socket,
        array $queries,
        array $options,
        ?Closure $debug
    ) {
        if (!class_exists(static::CLASS_MYSQLI)) {
            throw new AdapterException('mysqli extension is not installed');
        }

        if (!$socket) {
            throw new DriverException('this driver requires a socket');
        }

        parent::__construct(
            $driver,
            $name,
            $socket,
            $queries,
            $options,
            $debug
        );

        $isNetworkSocket = $socket instanceof NetworkSocket;

        $hostname = $isNetworkSocket
            ? (($options[static::OPTIONS_PERSISTENT] ?? false) ? sprintf('p:%s', $socket->host) : $socket->host)
            : null;

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $this->mysqli = new mysqli(
            $hostname,
            $socket->username,
            $socket->password,
            $name,
            $isNetworkSocket ? $socket->port : null,
            !$isNetworkSocket ? $socket->host : null
        );

        if (array_key_exists(static::OPTIONS_MYSQL_CHARSET, $options)) {
            $this->mysqlNames(
                (string) $options[static::OPTIONS_MYSQL_CHARSET],
                $options[static::OPTIONS_MYSQL_COLLATION] ?? null
            );
        }

        if (array_key_exists(static::OPTIONS_MYSQL_ENGINE, $options)) {
            $this->mysqlEngine((string) $options[static::OPTIONS_MYSQL_ENGINE]);
        }

        foreach ($queries as $query) {
            $this->exec($query);
        }
    }

    public function version(): int
    {
        return $this->mysqli->server_version;
    }

    public function exec(string $query): void
    {
        $start = microtime(true);

        try {
            $this->mysqli->query($query);
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

            throw $exception;
        }

        $this->debug($query, $start);
    }

    public function query(string $query): MySQLiResult
    {
        try {
            $start = microtime(true);

            $mysqliResult = $this->mysqli->query($query);

            $this->debug($query, $start);

            return new MySQLiResult($mysqliResult);
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

            throw $exception;
        }
    }

    public function queryWithParams(DialectInterface $dialect, QueryWithParams $queryWithParams, bool $emulatePrepare): MySQLiResult
    {
        $queryWithParams->namedParamsToQuestionMarks();

        if ($emulatePrepare) {
            return $this->query($queryWithParams->toSql($dialect));
        }

        $start = microtime(true);

        try {
            $mysqliStmt = $this->mysqli->prepare($queryWithParams->query);
        } catch (Throwable $exception) {
            $this->debug(fn (): string => $queryWithParams->toSql($dialect), $start, $exception);

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
            $this->debug(fn (): string => $queryWithParams->toSql($dialect), $start, $exception);

            throw $exception;
        }

        $mysqliResult = $mysqliStmt->get_result();

        $this->debug(fn (): string => $queryWithParams->toSql($dialect), $start);

        return new MySQLiResult($mysqliResult);
    }

    public function beginTransaction(DialectInterface $dialect, ?string $name = null): void
    {
        if ($this->inTransaction()) {
            return;
        }

        $this->mysqli->begin_transaction(0, $name);

        $this->inTransaction = true;
    }

    public function commitTransaction(DialectInterface $dialect, ?string $name = null): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        try {
            $this->mysqli->commit(0, $name);
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            $this->inTransaction = false;
        }
    }

    public function rollbackTransaction(DialectInterface $dialect, ?string $name = null): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        try {
            $this->mysqli->rollback(0, $name);
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            $this->inTransaction = false;
        }
    }

    public function lastInsertId(?string $name = null): int|string
    {
        return $this->mysqli->insert_id;
    }

    public function __destruct()
    {
        if (!($this->options[static::OPTIONS_PERSISTENT] ?? false)) {
            $this->mysqli->close();
        }
    }
}
