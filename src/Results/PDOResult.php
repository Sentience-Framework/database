<?php

namespace Sentience\Database\Results;

use PDO;
use PDOStatement;

class PDOResult extends ResultAbstract
{
    public function __construct(protected PDOStatement $pdoStatement)
    {
    }

    public function columns(): array
    {
        $columns = [];

        for ($i = 0; $i < $this->pdoStatement->columnCount(); $i++) {
            $meta = $this->pdoStatement->getColumnMeta($i);

            $name = $meta['name'];
            $nativeType = strtoupper($meta['native_type']);

            $columns[$name] = $nativeType;
        }

        return $columns;
    }

    public function fetchObject(string $class = 'stdClass', array $constructorArgs = []): ?object
    {
        $object = $this->pdoStatement->fetchObject($class, $constructorArgs);

        if (is_bool($object)) {
            return null;
        }

        return $object;
    }

    public function fetchObjects(string $class = 'stdClass', array $constructorArgs = []): array
    {
        return $this->pdoStatement->fetchAll(PDO::FETCH_CLASS, $class, $constructorArgs);
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
