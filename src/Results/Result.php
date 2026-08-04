<?php

namespace Sentience\Database\Results;

class Result extends ResultAbstract
{
    public static function fromInterface(ResultInterface $interface): static
    {
        return new static(
            $interface->columns(),
            $interface->fetchAssocs()
        );
    }

    public function __construct(
        protected array $columns,
        protected array $rows
    ) {
    }

    public function columns(): array
    {
        return $this->columns;
    }

    public function fetchAssoc(): ?array
    {
        $assoc = $this->rows[0] ?? null;

        if (is_null($assoc)) {
            return null;
        }

        $this->rows = array_slice($this->rows, 1);

        return $assoc;
    }

    public function fetchAssocs(): array
    {
        $rows = $this->rows;

        $this->rows = [];

        return $rows;
    }
}
