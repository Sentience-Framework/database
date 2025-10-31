<?php

namespace Sentience\Database\Results;

abstract class ResultAbstract implements ResultInterface
{
    public function scalar(): mixed
    {
        $assoc = $this->fetchAssoc();

        if (!$assoc) {
            return null;
        }

        return current($assoc);
    }

    public function fetchObject(string $class = 'stdClass', array $constructorArgs = []): ?object
    {
        $assoc = $this->fetchAssoc();

        if (is_null($assoc)) {
            return null;
        }

        $object = new $class(...$constructorArgs);

        return $this->mapAssocToObject($object, $assoc);
    }

    public function fetchObjects(string $class = 'stdClass', array $constructorArgs = []): array
    {
        $assocs = $this->fetchAssocs();

        $object = new $class(...$constructorArgs);

        return array_map(
            fn (array $assoc): object => $this->mapAssocToObject(clone $object, $assoc),
            $assocs
        );
    }

    protected function mapAssocToObject(object $object, array $assoc): object
    {
        foreach ($assoc as $key => $value) {
            $object->{$key} = $value;
        }

        return $object;
    }
}
