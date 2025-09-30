<?php

namespace Sentience\Database\Queries\Objects;

use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Exceptions\QueryWithParamsException;

class QueryWithParams
{
    public const string REGEX_PATTERN_QUESTION_MARKS = '/(\?)(?=(?:[^\'\"\`\\\\]|\'(?:\\\\.|[^\\\\\'])*\'|\"(?:\\\\.|[^\\\\\"])*\"|\`(?:\\\\.|[^\\\\\`])*\`)*$)/';
    public const string REGEX_PATTERN_NAMED_PARAMS = '/(\:\w+)(?=(?:[^\'\"\`\\\\]|\'(?:\\\\.|[^\\\\\'])*\'|\"(?:\\\\.|[^\\\\\"])*\"|\`(?:\\\\.|[^\\\\\`])*\`)*$)/';

    public function __construct(
        public string $query,
        public array $params = []
    ) {
    }

    public function namedParamsToQuestionMarks(): static
    {
        $params = [];

        $query = preg_replace_callback(
            static::REGEX_PATTERN_NAMED_PARAMS,
            function (array $matches) use (&$params): mixed {
                $key = $matches[1];

                if (!array_key_exists($key, $this->params)) {
                    throw new QueryWithParamsException("named param {$key} does not exist");
                }

                $params[] = $this->params[$key];

                return '?';
            },
            $this->query
        );

        if (count($params) == 0) {
            return $this;
        }

        $this->query = $query;
        $this->params = $params;

        return $this;
    }

    public function toRawQuery(DialectInterface $dialect): string
    {
        if (count($this->params) == 0) {
            return $this->query;
        }

        $params = array_map(
            fn (mixed $param): mixed => $dialect->castToQuery($param),
            $this->params
        );

        foreach ($params as $key => $value) {
            if (!is_numeric($key)) {
                return $this->toRawQueryNamedParams($params);
            }
        }

        return $this->toRawQueryQuestionMarks($params);
    }

    protected function toRawQueryQuestionMarks(array $params): string
    {
        $index = 0;

        return preg_replace_callback(
            static::REGEX_PATTERN_QUESTION_MARKS,
            function () use ($params, &$index): mixed {
                if (!array_key_exists($index, $params)) {
                    throw new QueryWithParamsException('question mark and value count do not match');
                }

                $value = $params[$index];

                $index++;

                return $value;
            },
            $this->query
        );
    }

    protected function toRawQueryNamedParams(array $params): string
    {
        return preg_replace_callback(
            static::REGEX_PATTERN_NAMED_PARAMS,
            function (array $matches) use ($params): mixed {
                $key = $matches[1];

                if (!array_key_exists($key, $params)) {
                    throw new QueryWithParamsException("named param {$key} does not exist");
                }

                return $params[$key];
            },
            $this->query
        );
    }
}
