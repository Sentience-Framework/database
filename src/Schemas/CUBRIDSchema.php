<?php

namespace Sentience\Database\Schemas;

use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Objects\Type;

class CUBRIDSchema extends MySQLSchema
{
    protected function type(string $type, ?int $size): string|Type
    {
        return match ($type) {
            'SHORT' => new Type(TypeEnum::Bool),
            'MONETARY',
            'NUMERIC',
            'DOUBLE PRECISION' => new Type(TypeEnum::Float, 64),
            'CHAR VARYING',
            'STRING' => new Type(TypeEnum::String, $size ?? 255),
            'LONG VARCHAR' => new Type(TypeEnum::String, $size ?? PHP_INT_MAX),
            default => parent::type($type, $size)
        };
    }
}
