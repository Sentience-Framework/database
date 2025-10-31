<?php

namespace Sentience\Database\Queries\Enums;

enum UnionEnum: string
{
    case UNION = 'UNION';
    case UNION_ALL = 'UNION ALL';
}
