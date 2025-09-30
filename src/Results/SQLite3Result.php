<?php

namespace Sentience\Database\Results;

class SQLite3Result implements ResultInterface
{
    public function __construct(protected \SQLite3Result $sqlite3Result)
    {
    }

    public function getColumns(): array
    {
        $columns = [];

        for ($i = 0; $i < $this->sqlite3Result->numColumns(); $i++) {
            $columns[] = $this->sqlite3Result->columnName($i);
        }

        return $columns;
    }

    public function fetchObject(string $class = 'stdClass', array $constructorArgs = []): ?object
    {
        $assoc = $this->fetchAssoc();

        if (is_null($assoc)) {
            return null;
        }

        $object = new $class(...$constructorArgs);

        foreach ($assoc as $key => $value) {
            $object->{$key} = $value;
        }

        return $object;
    }

    public function fetchObjects(string $class = 'stdClass', array $constructorArgs = []): array
    {
        $objects = [];

        while (true) {
            $object = $this->fetchObject($class, $constructorArgs);

            if (is_null($object)) {
                break;
            }

            $objects[] = $object;
        }

        return $objects;
    }

    public function fetchAssoc(): ?array
    {
        $assoc = $this->sqlite3Result->fetchArray(SQLITE3_ASSOC);

        if (is_bool($assoc)) {
            return null;
        }

        return $assoc;
    }

    public function fetchAssocs(): array
    {
        $assocs = [];

        while (true) {
            $assoc = $this->fetchAssoc();

            if (is_null($assoc)) {
                break;
            }

            $assocs[] = $assoc;
        }

        return $assocs;
    }
}
