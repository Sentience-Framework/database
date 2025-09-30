<?php

namespace Sentience\Database\Queries\Enums;

enum JoinEnum: string
{
    case LEFT_JOIN = 'LEFT JOIN';
    case INNER_JOIN = 'INNER JOIN';
}
