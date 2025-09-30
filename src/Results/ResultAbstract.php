<?php

namespace Sentience\Database\Results;

abstract class ResultAbstract implements ResultInterface
{
    public function fetchObject(string $class = 'stdClass', array $constructorArgs = []): ?object
    {
        $assoc = $this->fetchAssoc();

        if (is_null($assoc)) {
            return null;
        }

        $object = $this->constructClass($class, $constructorArgs);

        return $this->mapAssocToObject($object, $assoc);
    }

    public function fetchObjects(string $class = 'stdClass', array $constructorArgs = []): array
    {
        $assocs = $this->fetchAssocs();

        $object = $this->constructClass($class, $constructorArgs);

        return array_map(
            fn (array $assoc): object => $this->mapAssocToObject(clone $object, $assoc),
            $assocs
        );
    }

    protected function constructClass(string $class, array $constructorArgs): object
    {
        return $class(...$constructorArgs);
    }

    protected function mapAssocToObject(object $object, array $assoc): object
    {
        foreach ($assoc as $key => $value) {
            $object->{$key} = $value;
        }

        return $object;
    }
}
