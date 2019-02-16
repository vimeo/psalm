<?php

namespace Psalm\Plugin\Hook;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Type;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\CallMap;

interface MethodReturnTypeProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getMethodIds() : array;

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     */
    public static function get(
        StatementsAnalyzer $statements_analyzer,
        string $method_id,
        string $appearing_method_id,
        string $declaring_method_id,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ) : Type\Union;
}
