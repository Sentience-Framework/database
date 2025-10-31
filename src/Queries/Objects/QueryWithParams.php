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
    public const string REGEX_PATTERN = '/(?:\'(?:\\\\.|[^\\\\\'])*\'|\"(?:\\\\.|[^\\\\\"])*\"|\`(?:\\\\.|[^\\\\\`])*\`|(\?)|(?<!\:)(\:\w+)(?=(?:[^\'\"\`\\\\]|\'(?:\\\\.|[^\\\\\'])*\'|\"(?:\\\\.|[^\\\\\"])*\"|\`(?:\\\\.|[^\\\\\`])*\`)*$)|(?:\-\-[^\r\n]*|\/\*[\s\S]*?\*\/|\#.*))/m';

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
            static::REGEX_PATTERN,
            function (array $match) use (&$params, &$index): string {
                [$sql, $questionMark, $namedParm] = $match;

                if ($namedParm) {
                    if (!array_key_exists($namedParm, $this->params)) {
                        throw new QueryWithParamsException("named param {$namedParm} does not exist");
                    }

                    $params[] = $this->params[$namedParm];

                    return '?';
                }

                return $sql;
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

        $questionMarks = [];
        $namedParams = [];

        foreach ($this->params as $param => $value) {
            if ((bool) preg_match('/^[0-9]+$/', (string) $param)) {
                $questionMarks[] = $value;

                continue;
            }

            $namedParams[$param] = $value;
        }

        $index = 0;

        return $this->pregReplaceCallback(
            static::REGEX_PATTERN,
            function (array $match) use ($dialect, $questionMarks, $namedParams, &$index): string {
                [$sql, $questionMark, $namedParm] = $match;

                if ($questionMark) {
                    if (!array_key_exists($index, $questionMarks)) {
                        throw new QueryWithParamsException('question mark and param count do not match');
                    }

                    return $dialect->castToQuery($questionMarks[$index++]);
                }

                if ($namedParm) {
                    if (!array_key_exists($namedParm, $namedParams)) {
                        throw new QueryWithParamsException("named param {$namedParm} does not exist");
                    }

                    return $dialect->castToQuery($namedParams[$namedParm]);
                }

                return $sql;
            },
            $this->query
        );
    }

    protected function pregReplaceCallback(string $pattern, callable $callback, string $subject): string
    {
        $iniPcreJit = ini_get(static::INI_PCRE_JIT);
        $iniPcreBacktraceLimit = ini_get(static::INI_PCRE_BACKTRACE_LIMIT);
        $iniPcreRecursionLimit = ini_get(static::INI_PCRE_RECURSION_LIMIT);

        ini_set(static::INI_PCRE_JIT, (string) 0);
        ini_set(static::INI_PCRE_BACKTRACE_LIMIT, (string) PHP_INT_MAX);
        ini_set(static::INI_PCRE_RECURSION_LIMIT, (string) PHP_INT_MAX);

        try {
            return (string) preg_replace_callback($pattern, $callback, $subject, -1, $count, PREG_UNMATCHED_AS_NULL);
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
}
