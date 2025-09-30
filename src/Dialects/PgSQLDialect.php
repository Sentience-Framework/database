<?php

namespace Sentience\Database\Dialects;

use DateTime;
use Sentience\Database\Queries\Enums\ConditionEnum;
use Sentience\Database\Queries\Objects\Condition;
use Sentience\Database\Queries\Objects\OnConflict;
use Sentience\Database\Queries\Objects\Raw;

class PgSQLDialect extends SQLDialect
{
    protected const string DATETIME_FORMAT = 'Y-m-d H:i:s.u';
    protected const bool ON_CONFLICT = true;
    protected const bool RETURNING = true;

    protected function buildConditionRegex(string &$query, array &$params, Condition $condition): void
    {
        if ($this->version >= 1500) {
            parent::buildConditionRegex($query, $params, $condition);

            return;
        }

        $query .= sprintf(
            '%s %s ?',
            $this->escapeIdentifier($condition->identifier),
            $condition->condition == ConditionEnum::REGEX ? '~' : '!~'
        );

        [$pattern, $flags] = $condition->value;

        array_push(
            $params,
            !empty($flags)
            ? sprintf(
                '(?%s)%s',
                $flags,
                $pattern
            ) : $pattern
        );

        return;
    }

    protected function buildOnConflict(string &$query, array &$params, ?OnConflict $onConflict, array $values, ?string $lastInsertId): void
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
