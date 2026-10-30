<?php

namespace Sentience\Database\Queries\Traits;

use DateTimeInterface;
use Sentience\Database\Queries\Enums\ReferentialActionEnum;
use Sentience\Database\Queries\Enums\TypeEnum;
use Sentience\Database\Queries\Interfaces\Sql;
use Sentience\Database\Queries\Objects\AlterColumn;
use Sentience\Database\Queries\Objects\CheckConstraint;
use Sentience\Database\Queries\Objects\Column;
use Sentience\Database\Queries\Objects\DropColumn;
use Sentience\Database\Queries\Objects\DropConstraint;
use Sentience\Database\Queries\Objects\ForeignKeyConstraint;
use Sentience\Database\Queries\Objects\PrimaryKeys;
use Sentience\Database\Queries\Objects\RenameColumn;
use Sentience\Database\Queries\Objects\Type;
use Sentience\Database\Queries\Objects\UniqueConstraint;
use Sentience\Database\Queries\Query;

trait AltersTrait
{
    protected array $alters = [];

    public function addColumn(string $name, string|Type $type, bool $notNull = false, null|bool|int|float|string|DateTimeInterface|Sql $default = null, bool $generatedByDefaultAsIdentity = false): static
    {
        $this->alters[] = new Column($name, $type, $notNull, $default, $generatedByDefaultAsIdentity);

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
        $this->alters[] = new PrimaryKeys((array) $columns);

        return $this;
    }

    public function addUniqueConstraint(array $columns, ?string $name = null): static
    {
        $this->alters[] = new UniqueConstraint($columns, $name);

        return $this;
    }

    public function addForeignKeyConstraint(string|array $columns, string $referenceTable, string|array $referenceColumns, ?string $name = null, null|string|ReferentialActionEnum $onUpdate = null, null|string|ReferentialActionEnum $onDelete = null): static
    {
        $this->alters[] = new ForeignKeyConstraint((array) $columns, $referenceTable, (array) $referenceColumns, $name, $onUpdate, $onDelete);

        return $this;
    }

    public function addCheckConstraint(callable $check, ?string $name = null): static
    {
        $checkConstraint = new CheckConstraint($name);

        $checkConstraint = $check($checkConstraint) ?? $checkConstraint;

        if (!($checkConstraint instanceof CheckConstraints)) {
            return $this;
        }

        if (count($checkConstraint->conditions()) == 0) {
            return $this;
        }

        $this->alters[] = $checkConstraint;

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

    public function addAutoIncrement(string $name, int $bits = 64, bool $addPrimaryKey = true): static
    {
        return $this->addIdentity($name, $bits, $addPrimaryKey);
    }

    public function addIdentity(string $name, int $bits = 64, bool $addPrimaryKey = true): static
    {
        if ($addPrimaryKey) {
            $this->addPrimaryKeys($name);
        }

        return $this->addInt($name, $bits, true, null, true);
    }

    public function addBool(string $name, bool $notNull = false, null|bool|Sql $default = null): static
    {
        return $this->addColumn($name, new Type(TypeEnum::Bool), $notNull, $default);
    }

    public function addInt(string $name, int $bits = 64, bool $notNull = false, null|int|Sql $default = null, bool $generatedByDefaultAsIdentity = false): static
    {
        return $this->addColumn($name, new Type(TypeEnum::Int, $bits), $notNull, $default, $generatedByDefaultAsIdentity);
    }

    public function addFloat(string $name, int $bits = 64, bool $notNull = false, null|int|float|Sql $default = null): static
    {
        return $this->addColumn($name, new Type(TypeEnum::Float, $bits), $notNull, $default);
    }

    public function addString(string $name, int $length = 255, bool $notNull = false, null|string|Sql $default = null): static
    {
        return $this->addColumn($name, new Type(TypeEnum::String, $length), $notNull, $default);
    }

    public function addText(string $name, bool $notNull = false, null|string|Sql $default = null): static
    {
        return $this->addString($name, PHP_INT_MAX, $notNull, $default);
    }

    public function addDateTime(string $name, int $precision = 6, bool $notNull = false, null|DateTimeInterface|Sql $default = null): static
    {
        return $this->addColumn($name, new Type(TypeEnum::DateTime, $precision), $notNull, $default);
    }
}
