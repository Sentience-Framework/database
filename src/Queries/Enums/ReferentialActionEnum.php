<?php

namespace Sentience\Database\Queries\Enums;

enum ReferentialActionEnum: string
{
    case NoAction = 'NO ACTION';
    case Restrict = 'RESTRICT';
    case SetNull = 'SET NULL';
    case Cascade = 'CASCADE';
}
