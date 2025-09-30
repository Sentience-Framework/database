<?php

namespace Sentience\Database\Dialects;

use Sentience\Database\Queries\Objects\AlterColumn;
use Sentience\Database\Queries\Objects\DropConstraint;
use Sentience\Database\Queries\Objects\OnConflict;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Queries\Query;

class MySQLDialect extends SQLDialect implements DialectInterface
{
    public const bool ESCAPE_ANSI = false;
    public const string ESCAPE_IDENTIFIER = '`';
    public const string ESCAPE_STRING = '"';

    public function buildOnConflict(string &$query, array &$params, ?OnConflict $onConflict, array $values): void
    {
        if (is_null($onConflict)) {
            return;
        }

        $insertIgnore = is_null($onConflict->updates);

        if ($insertIgnore && !$onConflict->primaryKey) {
            $query = substr_replace($query, 'INSERT IGNORE', 0, 6);

            return;
        }

        $updates = !empty($onConflict->updates) ? $onConflict->updates : $values;

        if ($onConflict->primaryKey) {
            $lastInsertId = Query::raw(
                sprintf(
                    'LAST_INSERT_ID(%s)',
                    $this->escapeIdentifier($onConflict->primaryKey)
                )
            );

            $updates = $insertIgnore
                ? [$onConflict->primaryKey => $lastInsertId]
                : [...$updates, $onConflict->primaryKey => $lastInsertId];
        }

        $query .= sprintf(
            ' ON DUPLICATE KEY UPDATE %s',
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

    public function buildReturning(string &$query, ?array $returning): void
    {
        return;
    }

    public function buildAlterTableAlterColumn(AlterColumn $alterColumn): string
    {
        return substr_replace(
            parent::buildAlterTableAlterColumn($alterColumn),
            'MODIFY',
            0,
            5
        );
    }

    public function buildAlterTableDropConstraint(DropConstraint $dropConstraint): string
    {
        return substr_replace(
            parent::buildAlterTableDropConstraint($dropConstraint),
            'INDEX',
            5,
            10
        );
    }
}
