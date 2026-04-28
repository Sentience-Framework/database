<?php

namespace Sentience\Database\Dialects;

use DateTime;
use Sentience\Database\Queries\Enums\ConditionEnum;
use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Objects\Column;
use Sentience\Database\Queries\Objects\Condition;

class PgSQLDialect extends SQLDialect
{
    public const string OPTIONS_USE_TILDE_REGEX = 'use_tilde_regex';
    public const string OPTIONS_USE_SERIALS = 'use_serials';

    public const string DATETIME_FORMAT = 'Y-m-d H:i:s.u';
    public const array ESCAPE_CHARS = [
        '\\' => '\\\\',
        "\n" => '\\n',
        "\r" => '\\r',
        "\t" => '\\t',
        "\0" => '',
        "\b" => '\\b',
        "\x1A" => '\\x1A',
        "\f" => '\\f',
        "\v" => '\\v'
    ];
    public const bool BOOL = true;
    public const bool DISTINCT_ON = true;

    protected function buildConditionLike(string &$query, array &$params, Condition $condition): void
    {
        [$value, $caseInsensitive] = $condition->value;

        $query .= sprintf(
            '%s %s %s',
            $this->escapeIdentifier($condition->identifier),
            $condition->condition == ConditionEnum::LIKE
            ? ($caseInsensitive ? 'ILIKE' : ConditionEnum::LIKE->value)
            : ($caseInsensitive ? 'NOT ILIKE' : ConditionEnum::NOT_LIKE->value),
            $this->buildQuestionMarks($params, $value)
        );
    }

    protected function buildConditionRegex(string &$query, array &$params, Condition $condition): void
    {
        if ($this->version >= 1500 && !($this->options[static::OPTIONS_USE_TILDE_REGEX] ?? false)) {
            parent::buildConditionRegex($query, $params, $condition);

            return;
        }

        parent::buildConditionRegexOperator(
            $query,
            $params,
            $condition,
            '~',
            '!~'
        );
    }

    protected function buildColumn(Column $column): string
    {
        if (!$column->generatedByDefaultAsIdentity) {
            return parent::buildColumn($column);
        }

        if (!$this->generatedByDefaultAsIdentity() || $this->options[static::OPTIONS_USE_SERIALS] ?? false) {
            $typeIsUppercase = (bool) preg_match('/[A-Z]/', $column->type);

            $serialColumn = new Column(
                $column->name,
                match (strtoupper($column->type)) {
                    'SMALLINT',
                    'INTEGER',
                    'INT',
                    'INT2',
                    'INT4' => $typeIsUppercase ? 'SERIAL' : 'serial',
                    'BIGINT',
                    'INT8' => $typeIsUppercase ? 'BIGSERIAL' : 'bigserial',
                    default => $column->type
                },
                $column->notNull,
                $column->default,
                false
            );

            return parent::buildColumn($serialColumn);
        }

        return parent::buildColumn($column);
    }

    public function parseDateTime(string $string): ?DateTime
    {
        if ((bool) preg_match('/[\+\-][0-9]{2}$/', $string)) {
            $string .= ':00';
        }

        return parent::parseDateTime($string);
    }

    public function type(TypeEnum $type, ?int $size = null): string
    {
        return match ($type) {
            TypeEnum::FLOAT => $size > 32 ? 'DOUBLE PRECISION' : 'REAL',
            TypeEnum::DATETIME => 'TIMESTAMP',
            default => parent::type($type, $size)
        };
    }

    public function distinctOn(): bool
    {
        return $this->version >= 702;
    }

    public function generatedByDefaultAsIdentity(): bool
    {
        return $this->version >= 1700;
    }

    public function lateral(): bool
    {
        return $this->version >= 903;
    }

    public function onConflict(): bool
    {
        return $this->version >= 905;
    }

    public function returning(): bool
    {
        return $this->version >= 802;
    }
}
