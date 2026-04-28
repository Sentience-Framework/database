<?php

namespace Sentience\Database\Queries\Enums;

enum JoinEnum: string
{
    case JOIN = 'JOIN';
    case JOIN_LATERAL = 'JOIN LATERAL';
    case LEFT_JOIN = 'LEFT JOIN';
    case LEFT_JOIN_LATERAL = 'LEFT JOIN LATERAL';
    case INNER_JOIN = 'INNER JOIN';
    case INNER_JOIN_LATERAL = 'INNER JOIN LATERAL';
    case OUTER_APPLY = 'OUTER APPLY';
    case CROSS_JOIN = 'CROSS APPLY';

    public function lateral(): bool
    {
        return match ($this) {
            static::JOIN_LATERAL,
            static::LEFT_JOIN_LATERAL,
            static::INNER_JOIN_LATERAL,
            static::OUTER_APPLY,
            static::CROSS_JOIN => true,
            default => false
        };
    }
}
