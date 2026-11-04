<?php

namespace Sentience\Database\Results;

abstract class ResultAbstract implements ResultInterface
{
    public function scalar(?string $column = null): mixed
    {
        $assoc = $this->fetchAssoc();

        if (!$assoc) {
            return null;
        }

        return $column ? ($assoc[$column] ?? null) : current($assoc);
    }

    public function scalars(?string $column = null): array
    {
        $assocs = $this->fetchAssocs();

        return array_map(
            fn (array $assoc): mixed => $column ? ($assoc[$column] ?? null) : current($assoc),
            $assocs
        );
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
