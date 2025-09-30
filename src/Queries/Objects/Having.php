<?php

namespace Sentience\Database\Queries\Objects;

class Having extends QueryWithParams
{
    public function __construct(string $conditions, array $values)
    {
        parent::__construct($conditions, $values);

        $this->namedParamsToQuestionMarks();
    }
}
