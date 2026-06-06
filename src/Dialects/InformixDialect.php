<?php

namespace Sentience\Database\Dialects;

use Sentience\Database\DriverInterface;
use Sentience\Database\Queries\Enums\TypeEnum;

class InformixDialect extends SQLDialect
{
    public const array ESCAPE_CHARS = [
        '\\' => '\\\\',
        "\n" => '\\n',
        "\r" => '\\r',
        "\t" => '\\t',
        "\0" => '',
        "\b" => '\\b'
    ];
    public const bool LATERAL = true;
    public const bool RETURNING = true;

    public function __construct(DriverInterface $driver, int|string $version, array $options)
    {
        if (is_int($version)) {
            parent::__construct($driver, $version, $options);

            return;
        }

        preg_match('/Informix\s*(?:Dynamic\s*Server)\s*(\d+(\.\d+)?)/i', $version, $match);

        parent::__construct($driver, $match[1] ?? $version, $options);
    }

    protected function buildLimit(string &$query, ?int $limit, ?int $offset): void
    {
        if (is_null($limit)) {
            return;
        }

        $query = substr_replace(
            $query,
            sprintf('SELECT FIRST %d', $limit),
            0,
            6
        );
    }

    public function type(TypeEnum $type, ?int $size = null): string
    {
        return match ($type) {
            TypeEnum::BOOL => 'SMALLINT',
            TypeEnum::STRING => $size > 255 ? ($size > 2048 ? 'LONG VARCHAR' : 'VARCHAR') : sprintf('VARCHAR(%d)', $size ?? 255),
            TypeEnum::DATETIME => 'DATETIME YEAR TO FRACTION',
            default => parent::type($type, $size)
        };
    }

    public function returning(): bool
    {
        return true;
    }

    public function lateral(): bool
    {
        return true;
    }
}
