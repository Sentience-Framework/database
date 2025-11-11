<?php

namespace Sentience\Database\Adapters;

use mysqli;
use Throwable;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Exceptions\AdapterException;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Results\MySQLiResult;
use Sentience\Database\Results\Result;

class MySQLiAdapter extends AdapterAbstract
{
    public const string MYSQLI_NULL = 's';
    public const string MYSQLI_INT = 'i';
    public const string MYSQLI_FLOAT = 'd';
    public const string MYSQLI_STRING = 's';

    protected ?mysqli $mysqli = null;
    protected bool $inTransaction = false;

    public function connect(): void
    {
        if ($this->isConnected()) {
            return;
        }

        $this->mysqli = ($this->connect)();

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

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

    public function ping(DialectInterface $dialect, bool $reconnect = false): bool
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

        if ($this->lazy) {
            $this->disconnect();
        }

        return $version;
    }

    public function exec(DialectInterface $dialect, string $query): void
    {
        $this->connect();

        $start = microtime(true);

        try {
            $this->mysqli->query($query);
        } catch (Throwable $exception) {
            $this->debug($dialect, $query, $start, $exception);

            throw $exception;
        } finally {
            if ($this->lazy) {
                $this->disconnect();
            }
        }

        $this->debug($dialect, $query, $start);
    }

    public function query(DialectInterface $dialect, string $query): MySQLiResult|Result
    {
        $this->connect();

        try {
            $start = microtime(true);

            $mysqliResult = $this->mysqli->query($query);

            $this->debug($dialect, $query, $start);

            $result = new MySQLiResult($mysqliResult);

            return $this->lazy
                ? Result::fromInterface($result)
                : $result;
        } catch (Throwable $exception) {
            $this->debug($dialect, $query, $start, $exception);

            throw $exception;
        } finally {
            if ($this->lazy) {
                $this->disconnect();
            }
        }
    }

    public function queryWithParams(DialectInterface $dialect, QueryWithParams $queryWithParams, bool $emulatePrepare): MySQLiResult|Result
    {
        $this->connect();

        $queryWithParams->namedParamsToQuestionMarks();

        if ($emulatePrepare) {
            return $this->query($dialect, $queryWithParams->toSql($dialect));
        }

        $start = microtime(true);

        try {
            $mysqliStmt = $this->mysqli->prepare($queryWithParams->query);
        } catch (Throwable $exception) {
            if ($this->lazy) {
                $this->disconnect();
            }

            $this->debug($dialect, $queryWithParams, $start, $exception);

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
            if ($this->lazy) {
                $this->disconnect();
            }

            $this->debug($dialect, $queryWithParams, $start, $exception);

            throw $exception;
        }

        $mysqliResult = $mysqliStmt->get_result();

        $this->debug($dialect, $queryWithParams, $start);

        $result = new MySQLiResult($mysqliResult);

        if ($this->lazy) {
            $result = Result::fromInterface($result);

            $this->disconnect();
        }

        return $result;
    }

    public function beginTransaction(DialectInterface $dialect): void
    {
        $this->connect();

        if ($this->inTransaction()) {
            return;
        }

        $this->mysqli->begin_transaction();

        $this->inTransaction = true;
    }

    public function commitTransaction(DialectInterface $dialect): void
    {
        if (!$this->isConnected()) {
            return;
        }

        if (!$this->inTransaction()) {
            return;
        }

        try {
            $this->mysqli->commit();
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            $this->inTransaction = false;

            if ($this->lazy) {
                $this->disconnect();
            }
        }
    }

    public function rollbackTransaction(DialectInterface $dialect): void
    {
        if (!$this->isConnected()) {
            return;
        }

        if (!$this->inTransaction()) {
            return;
        }

        try {
            $this->mysqli->rollback();
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            $this->inTransaction = false;

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

        return $this->inTransaction;
    }

    public function lastInsertId(?string $name = null): null|int|string
    {
        if ($this->lazy) {
            throw new AdapterException('last insert id is not supported in lazy mode');
        }

        if (!$this->isConnected()) {
            return null;
        }

        return $this->mysqli->insert_id;
    }
}
