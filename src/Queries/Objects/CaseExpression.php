<?php

namespace Sentience\Database\Queries\Objects;

use DateTimeInterface;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Queries\Interfaces\Sql;
use Sentience\Database\Queries\SelectQuery;

class CaseExpression extends Expression
{
    protected ?array $compiled = null;
    protected array $whens = [];
    protected bool $hasElse = false;
    protected null|bool|int|float|string|DateTimeInterface|SelectQuery|Sql $else = null;

    public function __construct(protected null|string|array|Sql $identifier)
    {
    }

    public function when(null|bool|int|float|string|DateTimeInterface|SelectQuery|Sql $when, null|bool|int|float|string|DateTimeInterface|SelectQuery|Sql $then): static
    {
        $this->compiled = null;

        $this->whens[] = [$when, $then];

        return $this;
    }

    public function else(null|bool|int|float|string|DateTimeInterface|SelectQuery|Sql $else): static
    {
        $this->compiled = null;

        $this->hasElse = true;
        $this->else = $else;

        return $this;
    }

    public function sql(DialectInterface $dialect): string
    {
        if (!$this->compiled) {
            $this->compile($dialect);
        }

        return $this->compiled[0];
    }

    public function params(DialectInterface $dialect): array
    {
        if (!$this->compiled) {
            $this->compile($dialect);
        }

        return $this->compiled[1];
    }

    protected function compile(DialectInterface $dialect): void
    {
        $sql = 'CASE ';
        $params = [];

        if ($this->identifier) {
            $sql .= ' ';
            $sql .= $dialect->escapeIdentifier($this->identifier);
        }

        foreach ($this->whens as $case) {
            [$when, $then] = $case;

            $sql .= ' WHEN ? THEN ?';

            $params[] = $when;
            $params[] = $then;
        }

        if ($this->hasElse) {
            $sql .= ' ELSE ?';

            $params[] = $this->else;
        }

        $sql .= ' END';

        $this->compiled = [$sql, $params];
    }
}
