<?php

namespace Sentience\Database\Dialects;

use Sentience\Database\DriverInterface;

abstract class DialectAbstract implements DialectInterface
{
    protected int $version;
    protected array $options;

    public function __construct(protected DriverInterface $driver, int|string $version, array $options)
    {
        if (is_int($version)) {
            $this->version = $version;

            return;
        }

        $parts = explode(
            '.',
            strtok(
                $version,
                '-'
            )
        );

        $partsCount = count($parts);

        $number = 0;

        foreach ($parts as $index => $part) {
            $number += (int) $part * pow(100, $partsCount - $index - 1);
        }

        $this->version = $number;
        $this->options = [...($options[SQLDialect::class] ?? []), ...($options[static::class] ?? [])];
    }
}
