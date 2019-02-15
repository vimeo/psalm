<?php

namespace Psalm\Plugin\Hook;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Type;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\CallMap;

interface FunctionReturnTypeProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getFunctionIds() : array;

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     */
    public static function get(
        StatementsAnalyzer $statements_analyzer,
        string $function_id,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ) : Type\Union;
}
