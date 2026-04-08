<?php

namespace Sentience\Database\Queries\Objects;

use ArgumentCountError;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Interfaces\Sql;
use Sentience\Database\Queries\SelectQuery;

class ExpressionF implements Sql
{
    public const array BOOL_MODIFIERS = ['b'];
    public const array INT_MODIFIERS = ['c', 'd', 'o', 'u', 'x', 'X'];
    public const array FLOAT_MODIFIERS = ['e', 'E', 'f', 'F', 'g', 'G', 'h', 'H'];
    public const array STRING_MODIFIERS = ['s'];

    protected ?string $sql = null;
    protected ?array $params = null;

    public function __construct(
        protected string $format,
        protected array $values
    ) {
    }

    public function sql(DialectInterface $dialect): string
    {
        if (is_null($this->sql)) {
            $this->compile($dialect);
        }

        return $this->sql;
    }

    public function params(DialectInterface $dialect): array
    {
        if (is_null($this->params)) {
            $this->compile($dialect);
        }

        return $this->params;
    }

    public function rawSql(DialectInterface $dialect): string
    {
        $queryWithParams = new QueryWithParams(
            $this->sql($dialect),
            $this->params($dialect)
        );

        return $queryWithParams->toSql($dialect);
    }

    protected function compile(DialectInterface $dialect): void
    {
        $pattern = '/(?<!\%)\%(\.[0-9]{0,53})?(['
            . implode(
                '',
                [
                    ...static::BOOL_MODIFIERS,
                    ...static::INT_MODIFIERS,
                    ...static::FLOAT_MODIFIERS,
                    ...static::STRING_MODIFIERS
                ]
            )
            . '])/';

        $index = 0;

        $this->params = [];
        $this->sql = preg_replace_callback(
            $pattern,
            function (array $match) use ($dialect, &$index): string {
                if (!array_key_exists($index, $this->values)) {
                    throw new ArgumentCountError(
                        sprintf(
                            '%d arguments are required, %d given',
                            $index + 1,
                            count($this->values)
                        )
                    );
                }

                $value = $this->values[$index];

                $index++;

                if ($value instanceof SelectQuery) {
                    $queryWithParams = $value->toQueryWithParams();

                    array_push($this->params, ...$queryWithParams->params);

                    return sprintf(
                        '(%s)',
                        $queryWithParams->query
                    );
                }

                if ($value instanceof Sql) {
                    array_push($this->params, ...$value->params($dialect));

                    return $value->sql($dialect);
                }

                $precision = $match[1];
                $type = $match[2];

                $this->params[] = match (true) {
                    is_null($value) => null,
                    is_object($value) => $value,
                    in_array($type, static::BOOL_MODIFIERS) => (bool) $value,
                    in_array($type, static::INT_MODIFIERS) => (int) $value,
                    in_array($type, static::FLOAT_MODIFIERS) => strlen($precision) > 1
                    ? round($value, (int) substr($precision, 1))
                    : (float) $value,
                    default => (string) $value
                };

                return '?';
            },
            $this->format
        );
    }
}
