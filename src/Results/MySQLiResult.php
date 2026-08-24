<?php

namespace Sentience\Database\Results;

use mysqli_result;

class MySQLiResult extends ResultAbstract
{
    public const array COLUMN_TYPES = [
        MYSQLI_TYPE_BIT => 'bit',
        MYSQLI_TYPE_BLOB => 'blob',
        MYSQLI_TYPE_CHAR => 'char',
        MYSQLI_TYPE_DATE => 'date',
        MYSQLI_TYPE_DATETIME => 'datetime',
        MYSQLI_TYPE_DECIMAL => 'decimal',
        MYSQLI_TYPE_DOUBLE => 'double',
        MYSQLI_TYPE_ENUM => 'enum',
        MYSQLI_TYPE_FLOAT => 'float',
        MYSQLI_TYPE_GEOMETRY => 'geometry',
        MYSQLI_TYPE_INT24 => 'int24',
        MYSQLI_TYPE_JSON => 'json',
        MYSQLI_TYPE_LONG => 'long',
        MYSQLI_TYPE_LONGLONG => 'longlong',
        MYSQLI_TYPE_LONG_BLOB => 'long_blob',
        MYSQLI_TYPE_MEDIUM_BLOB => 'medium_blob',
        MYSQLI_TYPE_NEWDATE => 'newdate',
        MYSQLI_TYPE_NEWDECIMAL => 'newdecimal',
        MYSQLI_TYPE_NULL => 'null',
        MYSQLI_TYPE_SET => 'set',
        MYSQLI_TYPE_SHORT => 'short',
        MYSQLI_TYPE_STRING => 'string',
        MYSQLI_TYPE_TIME => 'time',
        MYSQLI_TYPE_TIMESTAMP => 'timestamp',
        MYSQLI_TYPE_TINY => 'tiny',
        MYSQLI_TYPE_TINY_BLOB => 'tiny_blob',
        MYSQLI_TYPE_VAR_STRING => 'var_string',
        MYSQLI_TYPE_YEAR => 'year'
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
            $type = static::COLUMN_TYPES[$field->type] ?? null;

            $columns[$name] = $type;
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
