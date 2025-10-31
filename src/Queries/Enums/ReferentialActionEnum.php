<?php

namespace Sentience\Database\Queries\Enums;

enum ReferentialActionEnum: string
{
    case ON_UPDATE_NO_ACTION = 'ON UPDATE NO ACTION';
    case ON_UPDATE_SET_NULL = 'ON UPDATE SET NULL';
    case ON_UPDATE_CASCADE = 'ON UPDATE CASCADE';
    case ON_DELETE_NO_ACTION = 'ON DELETE NO ACTION';
    case ON_DELETE_SET_NULL = 'ON DELETE SET NULL';
    case ON_DELETE_CASCADE = 'ON DELETE CASCADE';
}
