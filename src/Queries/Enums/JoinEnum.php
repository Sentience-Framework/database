<?php

namespace Sentience\Database\Queries\Enums;

enum JoinEnum: string
{
    case LEFT_JOIN = 'LEFT JOIN';
    case LEFT_JOIN_LATERAL = 'LEFT JOIN LATERAL';
    case INNER_JOIN = 'INNER JOIN';
    case INNER_JOIN_LATERAL = 'INNER JOIN LATERAL';
}
