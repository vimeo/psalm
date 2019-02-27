<?php

namespace Psalm\Plugin\Hook;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Type;
use Psalm\StatementsSource;

interface MethodReturnTypeProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getClassLikeNames() : array;

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     * @return ?Type\Union
     */
    public static function getMethodReturnType(
        StatementsSource $statements_source,
        string $fq_classlike_name,
        string $method_name,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    );
}
