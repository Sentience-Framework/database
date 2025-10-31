<?php

namespace Sentience\Database\Results;

use mysqli_result;

class MySQLiResult extends ResultAbstract
{
    public const COLUMN_TYPES = [
        MYSQLI_TYPE_BIT => 'BIT',
        MYSQLI_TYPE_BLOB => 'BLOB',
        MYSQLI_TYPE_CHAR => 'CHAR',
        MYSQLI_TYPE_DATE => 'DATE',
        MYSQLI_TYPE_DATETIME => 'DATETIME',
        MYSQLI_TYPE_DECIMAL => 'DECIMAL',
        MYSQLI_TYPE_DOUBLE => 'DOUBLE',
        MYSQLI_TYPE_ENUM => 'ENUM',
        MYSQLI_TYPE_FLOAT => 'FLOAT',
        MYSQLI_TYPE_GEOMETRY => 'GEOMETRY',
        MYSQLI_TYPE_INT24 => 'INT24',
        MYSQLI_TYPE_INTERVAL => 'INTERVAL',
        MYSQLI_TYPE_JSON => 'JSON',
        MYSQLI_TYPE_LONG => 'LONG',
        MYSQLI_TYPE_LONGLONG => 'LONGLONG',
        MYSQLI_TYPE_LONG_BLOB => 'LONG_BLOB',
        MYSQLI_TYPE_MEDIUM_BLOB => 'MEDIUM_BLOB',
        MYSQLI_TYPE_NEWDATE => 'NEWDATE',
        MYSQLI_TYPE_NEWDECIMAL => 'NEWDECIMAL',
        MYSQLI_TYPE_NULL => 'NULL',
        MYSQLI_TYPE_SET => 'SET',
        MYSQLI_TYPE_SHORT => 'SHORT',
        MYSQLI_TYPE_STRING => 'STRING',
        MYSQLI_TYPE_TIME => 'TIME',
        MYSQLI_TYPE_TIMESTAMP => 'TIMESTAMP',
        MYSQLI_TYPE_TINY => 'TINY',
        MYSQLI_TYPE_TINY_BLOB => 'TINY_BLOB',
        MYSQLI_TYPE_VAR_STRING => 'VAR_STRING',
        MYSQLI_TYPE_YEAR => 'YEAR'
    ];

    public function __construct(protected bool|mysqli_result $mysqliResult)
    {
    }

    public function columns(): array
    {
        if (is_bool($this->mysqliResult)) {
            return [];
        }

        $fields = $this->mysqliResult->fetch_fields();

        $columns = [];

        foreach ($fields as $field) {
            $name = $field->name;
            $nativeType = static::COLUMN_TYPES[$field->type] ?? null;

            $columns[$name] = $nativeType;
        }

        return $columns;
    }

    public function fetchObject(string $class = 'stdClass', array $constructorArgs = []): ?object
    {
        if (is_bool($this->mysqliResult)) {
            return null;
        }

        $object = $this->mysqliResult->fetch_object($class, $constructorArgs);

        if (is_bool($object)) {
            return null;
        }

        return $object;
    }

    public function fetchAssoc(): ?array
    {
        if (is_bool($this->mysqliResult)) {
            return null;
        }

        $assoc = $this->mysqliResult->fetch_assoc();

        if (is_bool($assoc)) {
            return null;
        }

        return $assoc;
    }

    public function fetchAssocs(): array
    {
        if (is_bool($this->mysqliResult)) {
            return [];
        }

        return $this->mysqliResult->fetch_all(MYSQLI_ASSOC);
    }
}
