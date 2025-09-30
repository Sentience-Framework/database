<?php

namespace Sentience\Database\Queries;

use DateTimeInterface;
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

    public function column(string $name, string $type, bool $notNull = false, null|bool|int|float|string|DateTimeInterface|Raw $default = null, array $options = []): static
    {
        $this->columns[] = new Column($name, $type, $notNull, $default, $options);

        return $this;
    }
}
