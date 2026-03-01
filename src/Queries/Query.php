<?php

namespace Sentience\Database\Queries;

use DateTime;
use Sentience\Database\Databases\DatabaseInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Interfaces\Sql;
use Sentience\Database\Queries\Objects\Alias;
use Sentience\Database\Queries\Objects\CaseExpression;
use Sentience\Database\Queries\Objects\Expression;
use Sentience\Database\Queries\Objects\Identifier;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Queries\Objects\SubQuery;

abstract class Query implements QueryInterface
{
    public function __construct(
        protected DatabaseInterface $database,
        protected DialectInterface $dialect,
        protected string|array|Alias|Sql|SubQuery $table
    ) {
    }

    public function toSql(): string|array
    {
        return $this->toQueryWithParams()->toSql($this->dialect);
    }

    public function execute(bool $emulatePrepare = false): mixed
    {
        $queryWithParams = $this->toQueryWithParams();

        return $this->database->queryWithParams($queryWithParams, $emulatePrepare);
    }

    public static function alias(string|array|Sql $identifier, string $alias): Alias
    {
        return new Alias($identifier, $alias);
    }

    public static function caseExpression(null|string|array|Sql $identifier = null): CaseExpression
    {
        return new CaseExpression($identifier);
    }

    public static function expression(string $sql, array $params): Expression
    {
        return new Expression($sql, $params);
    }

    public static function identifier(string|array|Sql $identifier): Identifier
    {
        return new Identifier($identifier);
    }

    public static function raw(string $sql): Raw
    {
        return new Raw($sql);
    }

    public static function subQuery(SelectQuery $selectQuery, string $alias): SubQuery
    {
        return new SubQuery($selectQuery, $alias);
    }

    public static function now(): DateTime
    {
        return new DateTime();
    }

    public static function escapeAnsi(string $string, array $chars): string
    {
        return static::escape($string, $chars, '$0$0');
    }

    public static function escapeBackslash(string $string, array $chars): string
    {
        return static::escape($string, ['\\', ...$chars]);
    }

    public static function escapeLikeChars(string $string, bool $escapeBackslash = false): string
    {
        $chars = ['%', '_', '-', '^', '[', ']'];

        if ($escapeBackslash) {
            array_unshift($chars, '\\');
        }

        return static::escape($string, $chars);
    }

    protected static function escape(string $string, array $chars, string $replacement = '\\\\$0', string $pattern = '/%s/'): string
    {
        $escaped = $string;

        foreach ($chars as $char) {
            $escaped = preg_replace(
                sprintf(
                    $pattern,
                    preg_quote((string) $char, '/')
                ),
                $replacement,
                $escaped
            );

            if (!is_string($escaped)) {
                return $string;
            }
        }

        return $escaped;
    }
}
