<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\Context;
use Psalm\CodeLocation;
use Psalm\Type;
use Psalm\StatementsSource;

class GetObjectVarsReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return [
            'get_object_vars'
        ];
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     */
    public static function getFunctionReturnType(
        StatementsSource $statements_source,
        string $function_id,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ) {
        if (isset($call_args[0]->value->inferredType)
             && $call_args[0]->value->inferredType->isObjectType()
        ) {
            return Type::parseString('array<string, mixed>');
        }
    }
}
