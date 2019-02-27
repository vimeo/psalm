<?php

namespace Psalm\Plugin\Hook;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Type;
use Psalm\StatementsSource;

interface FunctionExistenceProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getFunctionIds() : array;

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     * @return ?bool
     */
    public static function doesFunctionExist(
        StatementsSource $statements_source,
        string $function_id,
        Context $context,
        CodeLocation $code_location
    );
}
