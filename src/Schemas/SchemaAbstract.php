<?php

namespace Sentience\Database\Schemas;

use Sentience\Database\Queries\Objects\Type;

abstract class SchemaAbstract implements SchemaInterface
{
    protected function true(mixed $value): bool
    {
        if (is_null($value)) {
            return false;
        }

        return in_array(
            strtoupper((string) $value),
            [
                'YES',
                'TRUE',
                '1'
            ]
        );
    }

    protected function type(string $type, ?int $size): string|Type
    {
        return $type;
    }
}
