<?php

namespace Sentience\Database\Queries;

use DateTimeInterface;
use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Objects\Column;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Queries\Traits\ConstraintsTrait;
use Sentience\Database\Queries\Traits\IfNotExistsTrait;
use Sentience\Database\Queries\Traits\PrimaryKeysTrait;
use Sentience\Database\Results\ResultInterface;

class CreateTableQuery extends Query
{
    use ConstraintsTrait;
    use IfNotExistsTrait;
    use PrimaryKeysTrait;

    protected array $columns = [];

    public function toQueryWithParams(): QueryWithParams
    {
        return $this->dialect->createTable(
            $this->ifNotExists,
            $this->table,
            $this->columns,
            $this->primaryKeys,
            $this->constraints
        );
    }

    public function toSql(): string
    {
        return parent::toSql();
    }

    public function execute(bool $emulatePrepare = false): ResultInterface
    {
        return parent::execute($emulatePrepare);
    }

    public function column(string $name, string $type, bool $notNull = false, null|bool|int|float|string|DateTimeInterface|Raw $default = null, bool $generatedByDefaultAsIdentity = false): static
    {
        $this->columns[] = new Column($name, $type, $notNull, $default, $generatedByDefaultAsIdentity);

        return $this;
    }

    public function autoIncrement(string $name, int $bits = 64): static
    {
        return $this->identity($name, $bits);
    }

    public function identity(string $name, int $bits = 64): static
    {
        return $this->int($name, $bits, true, null, true);
    }

    public function bool(string $name, bool $notNull = false, null|bool|Raw $default = null): static
    {
        return $this->column($name, $this->dialect->type(TypeEnum::BOOL), $notNull, $default);
    }

    public function int(string $name, int $bits = 64, bool $notNull = false, null|int|Raw $default = null, bool $generatedByDefaultAsIdentity = false): static
    {
        return $this->column($name, $this->dialect->type(TypeEnum::INT, $bits), $notNull, $default, $generatedByDefaultAsIdentity);
    }

    public function float(string $name, int $bits = 64, bool $notNull = false, null|int|float|DateTimeInterface|Raw $default = null): static
    {
        return $this->column($name, $this->dialect->type(TypeEnum::FLOAT, $bits), $notNull, $default);
    }

    public function string(string $name, int $size = 255, bool $notNull = false, null|string|Raw $default = null): static
    {
        return $this->column($name, $this->dialect->type(TypeEnum::STRING, $size), $notNull, $default);
    }

    public function dateTime(string $name, int $size = 6, bool $notNull = false, null|DateTimeInterface|Raw $default = null): static
    {
        return $this->column($name, $this->dialect->type(TypeEnum::DATETIME, $size), $notNull, $default);
    }
}
