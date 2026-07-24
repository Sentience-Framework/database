<?php

namespace Sentience\Database\Queries\Enums;

enum ReferentialActionEnum: string
{
    case OnUpdateNoAction = 'ON UPDATE NO ACTION';
    case OnUpdateSetNull = 'ON UPDATE SET NULL';
    case OnUpdateCascade = 'ON UPDATE CASCADE';
    case OnDeleteNoAction = 'ON DELETE NO ACTION';
    case OnDeleteSetNull = 'ON DELETE SET NULL';
    case OnDeleteCascade = 'ON DELETE CASCADE';
}
