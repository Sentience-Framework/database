<?php

namespace Sentience\Database\Results;

interface ResultInterface
{
    public function getColumns(): array;
    public function fetchObject(string $class = 'stdClass'): ?object;
    public function fetchObjects(string $class = 'stdClass'): array;
    public function fetchAssoc(): ?array;
    public function fetchAssocs(): array;
}
