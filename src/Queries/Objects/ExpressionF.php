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
    protected ?string $rawSql = null;

    public function __construct(
        protected string $format,
        protected array $values
    ) {
    }

    public function sql(DialectInterface $dialect): string
    {
        if (is_null($this->sql)) {
            $this->build($dialect);
        }

        return $this->sql;
    }

    public function params(DialectInterface $dialect): array
    {
        if (is_null($this->params)) {
            $this->build($dialect);
        }

        return $this->params;
    }

    public function rawSql(DialectInterface $dialect): string
    {
        if (is_null($this->rawSql)) {
            $this->build($dialect);
        }

        return $this->rawSql;
    }

    protected function build(DialectInterface $dialect): void
    {
        $pattern = '/(?<!\%)\%(\.[0-9]{0,53})?(['
            . implode('', [
                ...static::BOOL_MODIFIERS,
                ...static::INT_MODIFIERS,
                ...static::FLOAT_MODIFIERS,
                ...static::STRING_MODIFIERS
            ])
            . '])/';

        $this->sql = '';
        $this->params = [];
        $this->rawSql = '';

        if (!preg_match_all($pattern, $this->format, $matches, PREG_OFFSET_CAPTURE)) {
            $this->sql = $this->format;
            $this->rawSql = $this->format;

            return;
        }

        $offset = 0;

        foreach ($matches[0] as $index => $match) {
            [$matchText, $matchOffset] = $match;

            $beforeMatch = substr($this->format, $offset, $matchOffset - $offset);

            $this->sql .= $beforeMatch;
            $this->rawSql .= $beforeMatch;

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

            if ($value instanceof SelectQuery) {
                $queryWithParams = $value->toQueryWithParams();

                array_push($this->params, ...$queryWithParams->params);

                $this->sql .= $queryWithParams->query;
                $this->rawSql .= $queryWithParams->toSql($dialect);
            } elseif ($value instanceof Sql) {
                array_push($this->params, ...$value->params($dialect));

                $this->sql .= $value->sql($dialect);
                $this->rawSql .= $value->rawSql($dialect);
            } else {
                $type = $matches[2][$index][0];
                $precision = $matches[1][$index][0];

                $param = match (true) {
                    is_null($value) => null,
                    is_object($value) => $value,
                    in_array($type, static::BOOL_MODIFIERS) => (bool) $value,
                    in_array($type, static::INT_MODIFIERS) => (int) $value,
                    in_array($type, static::FLOAT_MODIFIERS) => strlen($precision) > 1
                    ? round($value, (int) substr($precision, 1))
                    : (float) $value,
                    default => (string) $value
                };

                $this->sql .= '?';
                $this->params[] = $param;
                $this->rawSql .= $dialect->castToQuery($param);
            }

            $offset = $matchOffset + strlen($matchText);
        }

        $afterMatches = substr($this->format, $offset);

        $this->sql .= $afterMatches;
        $this->rawSql .= $afterMatches;
    }
}
