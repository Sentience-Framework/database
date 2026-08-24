<?php

namespace Sentience\Database\Queries\Enums;

enum UnionEnum: string
{
    case Union = 'UNION';
    case UnionAll = 'UNION ALL';
}
