<?php

namespace Sentience\Database\Adapters;

use Closure;
use mysqli;
use Throwable;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Driver;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Results\MySQLiResult;

class MySQLiAdapter extends AdapterAbstract
{
    public const string MYSQLI_NULL = 's';
    public const string MYSQLI_INT = 'i';
    public const string MYSQLI_FLOAT = 'd';
    public const string MYSQLI_STRING = 's';

    protected ?mysqli $mysqli;
    protected bool $inTransaction = false;

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
            fn (): mysqli => new mysqli(
                ($options[static::OPTIONS_PERSISTENT] ?? false) ? sprintf('p:%s', $host) : $host,
                $username,
                $password,
                $name,
                $port
            ),
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

        $this->mysqli = $connect();

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

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

        $query = $queryWithParams->toSql($dialect);

        if ($emulatePrepare) {
            return $this->query($query);
        }

        $start = microtime(true);

        try {
            $mysqliStmt = $this->mysqli->prepare($queryWithParams->query);
        } catch (Throwable $exception) {
            $this->debug($query, $start, $exception);

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
            $this->debug($query, $start, $exception);

            throw $exception;
        }

        $mysqliResult = $mysqliStmt->get_result();

        $this->debug($query, $start);

        return new MySQLiResult($mysqliResult);
    }

    public function beginTransaction(): void
    {
        if ($this->inTransaction()) {
            return;
        }

        $this->mysqli->begin_transaction();

        $this->inTransaction = true;
    }

    public function commitTransaction(): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        try {
            $this->mysqli->commit();
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            $this->inTransaction = false;
        }
    }

    public function rollbackTransaction(): void
    {
        if (!$this->inTransaction()) {
            return;
        }

        try {
            $this->mysqli->rollback();
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            $this->inTransaction = false;
        }
    }

    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }

    public function lastInsertId(?string $name = null): int|string
    {
        return $this->mysqli->insert_id;
    }

    public function disconnect(): void
    {
        $this->mysqli->close();

        $this->mysqli = null;
    }

    public function isConnected(): bool
    {
        return !is_null($this->mysqli);
    }
}
