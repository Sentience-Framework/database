<?php

namespace Sentience\Database\Queries;

use Sentience\Database\Queries\Objects\Column;
use Sentience\Database\Queries\Objects\QueryWithParams;
use Sentience\Database\Queries\Traits\ConstraintsTrait;
use Sentience\Database\Queries\Traits\IfNotExistsTrait;
use Sentience\Database\Results\ResultInterface;

class CreateTableQuery extends Query
{
    use ConstraintsTrait;
    use IfNotExistsTrait;

    protected array $columns = [];
    protected array $primaryKeys = [];

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

    public function toRawQuery(): string
    {
        return parent::toRawQuery();
    }

    public function execute(): ResultInterface
    {
        return parent::execute();
    }

    public function column(string $name, string $type, bool $notNull = false, mixed $default = null, array $options = []): static
    {
        $this->columns[] = new Column($name, $type, $notNull, $default, $options);

        return $this;
    }

    public function primaryKeys(string|array $columns): static
    {
        $this->primaryKeys = (array) $columns;

        return $this;
    }
}
