<?php

namespace Sentience\Database\Results;

use mysqli_result;

class MySQLiResult implements ResultInterface
{
    public function __construct(protected bool|mysqli_result $mysqliResult)
    {
    }

    public function getColumns(): array
    {
        if (is_bool($this->mysqliResult)) {
            return [];
        }

        return array_map(
            fn (object $field): string => $field->name,
            $this->mysqliResult->fetch_fields()
        );
    }

    public function fetchObject(string $class = 'stdClass', array $constructorArgs = []): ?object
    {
        if (is_bool($this->mysqliResult)) {
            return null;
        }

        $object = $this->mysqliResult->fetch_object($class, $constructorArgs);

        if (is_bool($object)) {
            return null;
        }

        return $object;
    }

    public function fetchObjects(string $class = 'stdClass', array $constructorArgs = []): array
    {
        if (is_bool($this->mysqliResult)) {
            return [];
        }

        $assocs = $this->mysqliResult->fetch_all(MYSQLI_ASSOC);

        return array_map(
            function (array $assoc) use ($class, $constructorArgs): object {
                $object = new $class(...$constructorArgs);

                foreach ($assoc as $key => $value) {
                    $object->{$key} = $value;
                }

                return $object;
            },
            $assocs
        );
    }

    public function fetchAssoc(): ?array
    {
        if (is_bool($this->mysqliResult)) {
            return null;
        }

        $assoc = $this->mysqliResult->fetch_assoc();

        if (is_bool($assoc)) {
            return null;
        }

        return $assoc;
    }

    public function fetchAssocs(): array
    {
        if (is_bool($this->mysqliResult)) {
            return [];
        }

        return $this->mysqliResult->fetch_all(MYSQLI_ASSOC);
    }
}
