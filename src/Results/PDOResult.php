<?php

namespace Sentience\Database\Results;

use PDO;
use PDOStatement;
use Throwable;

class PDOResult extends ResultAbstract
{
    public function __construct(protected PDOStatement $pdoStatement)
    {
    }

    public function columns(): array
    {
        $columns = [];

        for ($i = 0; $i < $this->pdoStatement->columnCount(); $i++) {
            $columns[] = $this->pdoStatement->getColumnMeta($i)['name'];
        }

        return $columns;
    }

    public function fetchObject(string $class = 'stdClass', array $constructorArgs = []): ?object
    {
        try {
            $object = $this->pdoStatement->fetchObject($class, $constructorArgs);
        } catch (Throwable $exception) {
            return null;
        }

        if (is_bool($object)) {
            return null;
        }

        return $object;
    }

    public function fetchObjects(string $class = 'stdClass', array $constructorArgs = []): array
    {
        try {
            return $this->pdoStatement->fetchAll(PDO::FETCH_CLASS, $class, $constructorArgs);
        } catch (Throwable $exception) {
            return [];
        }
    }

    public function fetchAssoc(): ?array
    {
        try {
            $assoc = $this->pdoStatement->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $exception) {
            return null;
        }

        if (is_bool($assoc)) {
            return null;
        }

        return $assoc;
    }

    public function fetchAssocs(): array
    {
        try {
            return $this->pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $exception) {
            return [];
        }
    }
}
