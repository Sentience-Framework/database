<?php

namespace Sentience\Database\Results;

class Result extends ResultAbstract
{
    protected array $columns;
    protected array $rows;

    public static function fromInterface(ResultInterface $interface): static
    {
        return new static(
            $interface->columns(),
            $interface->fetchAssocs()
        );
    }

    public function __construct(
        array $columns,
        array $rows
    ) {
        $this->columns = array_values(
            array_map(
                fn (mixed $column): string => (string) $column,
                $columns
            )
        );

        $this->rows = array_values(
            array_map(
                fn (mixed $row): array => (array) $row,
                $rows
            )
        );
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

    public function result(): mixed
    {
        return null;
    }
}
