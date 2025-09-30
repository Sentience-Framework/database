<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Queries\Enums\ChainEnum;
use Sentience\Database\Queries\Enums\ConditionEnum;

class RawCondition extends QueryWithParams
{
    public function __construct(
        string $expression,
        array $values,
        public ChainEnum $chain
    ) {
        parent::__construct($expression, $values);

        $this->namedParamsToQuestionMarks();
    }

    public function toCondition(): Condition
    {
        return new Condition(
            ConditionEnum::RAW,
            $this->query,
            $this->params,
            $this->chain
        );
    }
}
