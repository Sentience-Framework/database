<?php

namespace Sentience\Database\Queries\Objects;

use Throwable;
use Sentience\Database\Dialects\DialectInterface;
use Sentience\Database\Exceptions\QueryWithParamsException;

class QueryWithParams
{
    public const string INI_PCRE_JIT = 'pcre.jit';
    public const string INI_PCRE_BACKTRACE_LIMIT = 'pcre.backtrack_limit';
    public const string INI_PCRE_RECURSION_LIMIT = 'pcre.recursion_limit';
    public const string REGEX_PATTERN_QUESTION_MARKS = '/(?:\'(?:\\\\.|[^\\\\\'])*\'|\"(?:\\\\.|[^\\\\\"])*\"|\`(?:\\\\.|[^\\\\\`])*\`|(\?)(?=(?:[^\'\"\`\\\\]|\'(?:\\\\.|[^\\\\\'])*\'|\"(?:\\\\.|[^\\\\\"])*\"|\`(?:\\\\.|[^\\\\\`])*\`)*$)|(?:\-\-[^\r\n]*|\/\*[\s\S]*?\*\/|\#.*))/m';
    public const string REGEX_PATTERN_NAMED_PARAMS = '/(?:\'(?:\\\\.|[^\\\\\'])*\'|\"(?:\\\\.|[^\\\\\"])*\"|\`(?:\\\\.|[^\\\\\`])*\`|(?<!\:)(\:\w+)(?=(?:[^\'\"\`\\\\]|\'(?:\\\\.|[^\\\\\'])*\'|\"(?:\\\\.|[^\\\\\"])*\"|\`(?:\\\\.|[^\\\\\`])*\`)*$)|(?:\-\-[^\r\n]*|\/\*[\s\S]*?\*\/|\#.*))/m';

    public function __construct(
        public string $query,
        public array $params = []
    ) {
    }

    public function namedParamsToQuestionMarks(): static
    {
        if (count($this->params) == 0) {
            return $this;
        }

        $params = [];

        $query = $this->pregReplaceCallback(
            static::REGEX_PATTERN_NAMED_PARAMS,
            function (array $match) use (&$params): string {
                if (!$this->isQuestionMarkOrNamedParamMatch($match)) {
                    return $match[0];
                }

                $key = $match[1];

                if (!array_key_exists($key, $this->params)) {
                    $this->throwNamedParamDoesNotExistException($key);
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

    public function toSql(DialectInterface $dialect): string
    {
        if (count($this->params) == 0) {
            return $this->query;
        }

        $params = array_map(
            fn (mixed $param): mixed => $dialect->castToQuery($param),
            $this->params
        );

        return array_is_list($params)
            ? $this->toSqlQuestionMarks($params)
            : $this->toSqlNamedParams($params);
    }

    protected function toSqlQuestionMarks(array $params): string
    {
        $index = 0;

        return $this->pregReplaceCallback(
            static::REGEX_PATTERN_QUESTION_MARKS,
            function (array $match) use ($params, &$index): string {
                if (!$this->isQuestionMarkOrNamedParamMatch($match)) {
                    return $match[0];
                }

                if (!array_key_exists($index, $params)) {
                    throw new QueryWithParamsException('question mark and value count do not match');
                }

                $value = $params[$index];

                $index++;

                return (string) $value;
            },
            $this->query
        );
    }

    protected function toSqlNamedParams(array $params): string
    {
        return $this->pregReplaceCallback(
            static::REGEX_PATTERN_NAMED_PARAMS,
            function (array $match) use ($params): string {
                if (!$this->isQuestionMarkOrNamedParamMatch($match)) {
                    return $match[0];
                }

                $key = $match[1];

                if (!array_key_exists($key, $params)) {
                    $this->throwNamedParamDoesNotExistException($key);
                }

                return (string) $params[$key];
            },
            $this->query
        );
    }

    protected function pregReplaceCallback(string|array $pattern, callable $callback, string|array $subject): null|string|array
    {
        $iniPcreJit = ini_get(static::INI_PCRE_JIT);
        $iniPcreBacktraceLimit = ini_get(static::INI_PCRE_BACKTRACE_LIMIT);
        $iniPcreRecursionLimit = ini_get(static::INI_PCRE_RECURSION_LIMIT);

        ini_set(static::INI_PCRE_JIT, (string) 0);
        ini_set(static::INI_PCRE_BACKTRACE_LIMIT, (string) PHP_INT_MAX);
        ini_set(static::INI_PCRE_RECURSION_LIMIT, (string) PHP_INT_MAX);

        try {
            return preg_replace_callback($pattern, $callback, $subject);
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            if (!is_bool($iniPcreJit)) {
                ini_set(static::INI_PCRE_JIT, $iniPcreJit);
            }

            if (!is_bool($iniPcreBacktraceLimit)) {
                ini_set(static::INI_PCRE_BACKTRACE_LIMIT, $iniPcreBacktraceLimit);
            }

            if (!is_bool($iniPcreRecursionLimit)) {
                ini_set(static::INI_PCRE_RECURSION_LIMIT, $iniPcreRecursionLimit);
            }
        }
    }

    protected function isQuestionMarkOrNamedParamMatch(array $match): bool
    {
        return count($match) > 1;
    }

    protected function throwNamedParamDoesNotExistException(string $key): void
    {
        throw new QueryWithParamsException("named param {$key} does not exist");
    }
}
