<?php

namespace Sentience\Database\Dialects;

use DateTime;
use Sentience\Database\Queries\Objects\OnConflict;
use Sentience\Database\Queries\Objects\Raw;

class PgSQLDialect extends SQLDialect
{
    public const string FUNCTION_REGEX = '~';
    public const string FUNCTION_NOT_REGEX = '!~';

    public function buildOnConflict(string &$query, array &$params, ?OnConflict $onConflict, array $values): void
    {
        if (is_null($onConflict)) {
            return;
        }

        $conflict = is_string($onConflict->conflict)
            ? sprintf('ON CONSTRAINT %s', $this->escapeIdentifier($onConflict->conflict))
            : sprintf(
                '(%s)',
                implode(
                    ', ',
                    array_map(
                        fn (string|Raw $column): string => $this->escapeIdentifier($column),
                        $onConflict->conflict
                    )
                )
            );

        if (is_null($onConflict->updates)) {
            $query .= sprintf(' ON CONFLICT %s DO NOTHING', $conflict);

            return;
        }

        $updates = count($onConflict->updates) > 0 ? $onConflict->updates : $values;

        $query .= sprintf(
            ' ON CONFLICT %s DO UPDATE SET %s',
            $conflict,
            implode(
                ', ',
                array_map(
                    function (mixed $value, string $key) use (&$params): string {
                        if ($value instanceof Raw) {
                            return sprintf(
                                '%s = %s',
                                $this->escapeIdentifier($key),
                                (string) $value
                            );
                        }

                        $params[] = $value;

                        return sprintf('%s = ?', $this->escapeIdentifier($key));
                    },
                    $updates,
                    array_keys($updates)
                )
            )
        );
    }

    public function castToQuery(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        return parent::castToQuery($value);
    }

    public function castBool(bool $bool): bool
    {
        return $bool;
    }

    public function parseBool(mixed $bool): bool
    {
        return $bool;
    }

    public function parseDateTime(string $string): ?DateTime
    {
        if (preg_match('/[\+\-][0-9]{2}$/', $string)) {
            $string .= ':00';
        }

        return parent::parseDateTime($string);
    }
}
