<?php

namespace Sentience\Database\Queries\Enums;

enum ConditionEnum: string
{
    case Equals = '=';
    case NotEquals = '<>';
    case LessThan = '<';
    case LessThanOrEquals = '<=';
    case GreaterThan = '>';
    case GreaterThanOrEquals = '>=';
    case Between = 'BETWEEN';
    case NotBetween = 'NOT BETWEEN';
    case Like = 'LIKE';
    case NotLike = 'NOT LIKE';
    case Glob = 'GLOB';
    case NotGlob = 'NOT GLOB';
    case In = 'IN';
    case NotIn = 'NOT IN';
    case Regex = 'REGEX';
    case NotRegex = 'NOT REGEX';
    case Exists = 'EXISTS';
    case NotExists = 'NOT EXISTS';
    case Raw = 'RAW';
}
