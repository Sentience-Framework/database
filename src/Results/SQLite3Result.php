<?php

namespace Sentience\Database\Results;

class SQLite3Result extends ResultAbstract
{
    public function __construct(protected \SQLite3Result $sqlite3Result)
    {
    }

    public function columns(): array
    {
        $columns = [];

        for ($i = 0; $i < $this->sqlite3Result->numColumns(); $i++) {
            $columns[] = $this->sqlite3Result->columnName($i);
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
