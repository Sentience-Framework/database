<?php

namespace Sentience\Database\Dialects;

use Sentience\Database\Driver;

abstract class DialectAbstract implements DialectInterface
{
    protected int $version;

    public function __construct(protected Driver $driver, int|string $version)
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
    }
}
