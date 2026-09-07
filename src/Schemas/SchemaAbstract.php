<?php

namespace Sentience\Database\Schemas;

use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Objects\Type;

abstract class SchemaAbstract implements SchemaInterface
{
    protected function type(string $type, ?int $size): string|Type
    {
        return match ($type) {
            'BOOLEAN',
            'BOOL' => new Type(TypeEnum::Bool),
            'INTEGER',
            'INT' => new Type(TypeEnum::Int, 32),
            'BIGINT' => new Type(TypeEnum::Int, 64),
            'REAL',
            'FLOAT',
            'DOUBLE',
            'DECIMAL' => new Type(TypeEnum::Float, 64),
            'VARCHAR',
            'TEXT' => new Type(TypeEnum::String, $size ?? PHP_INT_MAX),
            'DATETIME',
            'TIMESTAMP' => new Type(TypeEnum::DateTime, $size ?? 0),
            default => !is_null($size) ? sprintf('%s(%d)', $type, $size) : $type
        };
    }

    protected function true(mixed $value): bool
    {
        if (is_null($value)) {
            return false;
        }

        return in_array(
            strtoupper((string) $value),
            [
                'Y',
                'YES',
                'TRUE',
                '1'
            ]
        );
    }
}
