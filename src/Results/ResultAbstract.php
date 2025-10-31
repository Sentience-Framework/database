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

        return $this->assocToObject(
            new $class(...$constructorArgs),
            $assoc
        );
    }

    public function fetchObjects(string $class = 'stdClass', array $constructorArgs = []): array
    {
        $assocs = $this->fetchAssocs();

        return array_map(
            fn (array $assoc): object => $this->assocToObject(
                new $class(...$constructorArgs),
                $assoc
            ),
            $assocs
        );
    }

    protected function assocToObject(object $object, array $assoc): object
    {
        foreach ($assoc as $key => $value) {
            $object->{$key} = $value;
        }

        return $object;
    }
}
