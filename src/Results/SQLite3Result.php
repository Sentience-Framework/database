<?php

namespace Sentience\Database\Results;

class SQLite3Result extends ResultAbstract
{
    public const array COLUMN_TYPES = [
        SQLITE3_NULL => 'NULL',
        SQLITE3_INTEGER => 'INTEGER',
        SQLITE3_FLOAT => 'FLOAT',
        SQLITE3_TEXT => 'TEXT',
        SQLITE3_BLOB => 'BLOB'
    ];

    public function __construct(protected \SQLite3Result $sqlite3Result)
    {
    }

    public function columns(): array
    {
        $columns = [];

        for ($i = 0; $i < $this->sqlite3Result->numColumns(); $i++) {
            $name = $this->sqlite3Result->columnName($i);
            $nativeType = static::COLUMN_TYPES[$this->sqlite3Result->columnType($i)] ?? static::COLUMN_TYPES[SQLITE3_NULL];

            $columns[$name] = $nativeType;
        }

        return $columns;
    }

    public function fetchAssoc(): ?array
    {
        $assoc = $this->sqlite3Result->fetchArray(SQLITE3_ASSOC);

        if (is_bool($assoc)) {
            return null;
        }

        return $assoc;
    }

    public function fetchAssocs(): array
    {
        $assocs = [];

        while (true) {
            $assoc = $this->fetchAssoc();

            if (is_null($assoc)) {
                break;
            }

            $assocs[] = $assoc;
        }

        return $assocs;
    }
}
