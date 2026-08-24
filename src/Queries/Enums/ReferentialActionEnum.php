<?php

namespace Sentience\Database\Queries\Enums;

enum ReferentialActionEnum: string
{
    case NoAction = 'NO ACTION';
    case SetNull = 'SET NULL';
    case Cascade = 'CASCADE';
}
