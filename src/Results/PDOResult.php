<?php

namespace Sentience\Database\Results;

use PDO;
use PDOStatement;

class PDOResult implements ResultInterface
{
    public function __construct(protected PDOStatement $pdoStatement)
    {
    }

    public function getColumns(): array
    {
        $columns = [];

        $index = 0;

        while (true) {
            $column = $this->pdoStatement->getColumnMeta($index);

            if (is_bool($column)) {
                break;
            }

            $index++;

            $columns[] = $column['name'];
        }

        return $columns;
    }

    public function fetchObject(string $class = 'stdClass'): ?object
    {
        $object = $this->pdoStatement->fetchObject($class);

        if (is_bool($object)) {
            return null;
        }

        return $object;
    }

    public function fetchObjects(string $class = 'stdClass'): array
    {
        return $this->pdoStatement->fetchAll(PDO::FETCH_CLASS, $class);
    }

    public function fetchAssoc(): ?array
    {
        $assoc = $this->pdoStatement->fetch(PDO::FETCH_ASSOC);

        if (is_bool($assoc)) {
            return null;
        }

        return $assoc;
    }

    public function fetchAssocs(): array
    {
        return $this->pdoStatement->fetchAll(PDO::FETCH_ASSOC);
    }
}
