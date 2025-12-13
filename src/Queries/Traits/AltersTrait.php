<?php

namespace Sentience\Database\Queries\Traits;

use DateTimeInterface;
use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Objects\AddColumn;
use Sentience\Database\Queries\Objects\AddForeignKeyConstraint;
use Sentience\Database\Queries\Objects\AddPrimaryKeys;
use Sentience\Database\Queries\Objects\AddUniqueConstraint;
use Sentience\Database\Queries\Objects\AlterColumn;
use Sentience\Database\Queries\Objects\DropColumn;
use Sentience\Database\Queries\Objects\DropConstraint;
use Sentience\Database\Queries\Objects\Raw;
use Sentience\Database\Queries\Objects\RenameColumn;
use Sentience\Database\Queries\Query;

trait AltersTrait
{
    protected array $alters = [];

    public function addColumn(string $name, string $type, bool $notNull = false, null|bool|int|float|string|DateTimeInterface|Raw $default = null, bool $generatedByDefaultAsIdentity = false): static
    {
        $this->alters[] = new AddColumn($name, $type, $notNull, $default, $generatedByDefaultAsIdentity);

        return $this;
    }

    public function alterColumn(string $column, string $sql): static
    {
        $this->alters[] = new AlterColumn($column, $sql);

        return $this;
    }

    public function renameColumn(string $old, string $new): static
    {
        $this->alters[] = new RenameColumn($old, $new);

        return $this;
    }

    public function dropColumn(string $column): static
    {
        $this->alters[] = new DropColumn($column);

        return $this;
    }

    public function addPrimaryKeys(string|array $columns): static
    {
        $this->alters[] = new AddPrimaryKeys((array) $columns);

        return $this;
    }

    public function addUniqueConstraint(array $columns, ?string $name): static
    {
        $this->alters[] = new AddUniqueConstraint($columns, $name);

        return $this;
    }

    public function addForeignKeyConstraint(string $column, string $referenceTable, string $referenceColumn, ?string $name = null, array $referentialActions = []): static
    {
        $this->alters[] = new AddForeignKeyConstraint($column, $referenceTable, $referenceColumn, $name, $referentialActions);

        return $this;
    }

    public function dropConstraint(string $constraint): static
    {
        $this->alters[] = new DropConstraint($constraint);

        return $this;
    }

    public function alter(string $sql): static
    {
        $this->alters[] = Query::raw($sql);

        return $this;
    }

    public function addAutoIncrement(string $name, int $bits = 64): static
    {
        return $this->addIdentity($name, $bits);
    }

    public function addIdentity(string $name, int $bits = 64): static
    {
        return $this->addInt($name, $bits, true, null, true);
    }

    public function addBool(string $name, bool $notNull = false, null|bool|Raw $default = null): static
    {
        return $this->column($name, $this->dialect->type(TypeEnum::BOOL), $notNull, $default);
    }

    public function addInt(string $name, int $bits = 64, bool $notNull = false, null|int|Raw $default = null, bool $generatedByDefaultAsIdentity = false): static
    {
        return $this->column($name, $this->dialect->type(TypeEnum::INT, $bits), $notNull, $default, $generatedByDefaultAsIdentity);
    }

    public function addFloat(string $name, int $bits = 64, bool $notNull = false, null|int|float|Raw $default = null): static
    {
        return $this->column($name, $this->dialect->type(TypeEnum::FLOAT, $bits), $notNull, $default);
    }

    public function addString(string $name, int $size = 255, bool $notNull = false, null|string|Raw $default = null): static
    {
        return $this->column($name, $this->dialect->type(TypeEnum::STRING, $size), $notNull, $default);
    }

    public function addDateTime(string $name, int $size = 6, bool $notNull = false, null|DateTimeInterface|Raw $default = null): static
    {
        return $this->column($name, $this->dialect->type(TypeEnum::DATETIME, $size), $notNull, $default);
    }
}
