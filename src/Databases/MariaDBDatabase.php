<?php

namespace Sentience\Database\Databases;

use Sentience\Database\Driver;

class MariaDBDatabase extends MySQLDatabase
{
    public const Driver DRIVER = Driver::MARIADB;
}
