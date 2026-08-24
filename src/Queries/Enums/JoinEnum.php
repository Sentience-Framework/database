<?php

namespace Sentience\Database\Queries\Enums;

enum JoinEnum: string
{
    case LeftJoin = 'LEFT JOIN';
    case LeftJoinLateral = 'LEFT JOIN LATERAL';
    case InnerJoin = 'INNER JOIN';
    case InnerJoinLateral = 'INNER JOIN LATERAL';
    case CrossJoin = 'CROSS JOIN';
    case CrossJoinLateral = 'CROSS JOIN LATERAL';
}
