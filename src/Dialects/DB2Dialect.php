<?php

namespace Sentience\Database\Dialects;

use Sentience\Database\DriverInterface;
use Sentience\Database\Queries\Enums\TypeEnum;

class DB2Dialect extends SQLDialect
{
    public const array ESCAPE_CHARS = [
        '\\' => '\\\\',
        "\n" => '\\n',
        "\r" => '\\r',
        "\t" => '\\t',
        "\0" => '',
        "\b" => '\\b'
    ];
    public const bool RETURNING = true;

    public function __construct(DriverInterface $driver, int|string $version, array $options)
    {
        if (is_int($version)) {
            parent::__construct($driver, $version, $options);

            return;
        }

        preg_match('/IBM\s*DB2\s*(\d+(\.\d+)?)/i', $version, $match);

        parent::__construct($driver, $match[1] ?? $version, $options);
    }

    protected function buildLimit(string &$query, ?int $limit, ?int $offset): void
    {
        if (is_null($limit)) {
            return;
        }

        $query .= " FETCH FIRST {$limit} ROWS ONLY";
    }

    protected function buildOffset(string &$query, ?int $limit, ?int $offset): void
    {
        if (is_null($limit)) {
            return;
        }

        if (is_null($offset)) {
            return;
        }

        $query .= " OFFSET {$offset} ROWS";
    }

    public function type(TypeEnum $type, ?int $size = null): string
    {
        return match ($type) {
            TypeEnum::BOOL => 'SMALLINT',
            TypeEnum::INT => $size > 32 ? 'BIGINT' : 'INTEGER',
            TypeEnum::FLOAT => $size > 32 ? 'DOUBLE' : 'DECIMAL(15, 7)',
            TypeEnum::STRING => $size > 255 ? 'CLOB(1M)' : sprintf('VARCHAR(%d)', $size ?? 255),
            TypeEnum::DATETIME => 'TIMESTAMP',
            default => parent::type($type, $size)
        };
    }

    public function lateral(): bool
    {
        return $this->version >= 970;
    }
}
