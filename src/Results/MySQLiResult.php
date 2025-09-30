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
        return array_map(
            fn (object $field): string => $field->name,
            $this->mysqliResult->fetch_fields()
        );
    }

    public function fetchObject(string $class = 'stdClass'): ?object
    {
        if (!$this->mysqliResult) {
            return null;
        }

        $object = $this->mysqliResult->fetch_object($class);

        if (is_bool($object)) {
            return null;
        }

        return $object;
    }

    public function fetchObjects(string $class = 'stdClass'): array
    {
        if (!$this->mysqliResult) {
            return [];
        }

        $assocs = $this->mysqliResult->fetch_all(MYSQLI_ASSOC);

        return array_map(
            function (array $assoc) use ($class): object {
                $object = new $class();

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
        if (!$this->mysqliResult) {
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
        if (!$this->mysqliResult) {
            return [];
        }

        return $this->mysqliResult->fetch_all(MYSQLI_ASSOC);
    }
}
