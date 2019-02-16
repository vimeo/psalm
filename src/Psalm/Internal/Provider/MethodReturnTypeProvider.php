<?php

namespace Psalm\Internal\Provider;

use PhpParser;
use Psalm\Context;
use Psalm\CodeLocation;
use Psalm\Type;
use Psalm\Internal\Analyzer\StatementsAnalyzer;

class MethodReturnTypeProvider
{
    /**
     * @var array<
     *   string,
     *   \Closure(
     *     StatementsAnalyzer,
     *     string,
     *     string,
     *     string,
     *     array<PhpParser\Node\Arg>,
     *     Context,
     *     CodeLocation
     *   ) : Type\Union
     * >
     */
    private static $handlers = [];

    public function __construct()
    {
        self::$handlers = [];
    }

    /**
     * @return void
     */
    public function registerClosure(string $method_id, \Closure $c)
    {
        self::$handlers[strtolower($method_id)] = $c;
    }

    public function has(string $method_id) : bool
    {
        return isset(self::$handlers[strtolower($method_id)]);
    }

    /**
     * @param  array<PhpParser\Node\Arg>  $call_args
     */
    public function getReturnType(
        StatementsAnalyzer $statements_analyzer,
        string $method_id,
        string $appearing_method_id,
        string $declaring_method_id,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ) : Type\Union {
        return self::$handlers[strtolower($method_id)](
            $statements_analyzer,
            $method_id,
            $appearing_method_id,
            $declaring_method_id,
            $call_args,
            $context,
            $code_location
        );
    }
}
