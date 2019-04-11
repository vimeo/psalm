<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\Context;
use Psalm\CodeLocation;
use Psalm\Type;
use Psalm\StatementsSource;

class FirstArgStringReturnTypeProvider implements \Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds() : array
    {
        return [
            'crypt',
            'date'
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
        $return_type = Type::getString();

        if (isset($call_args[0]->value->inferredType)
             && $call_args[0]->value->inferredType->isString()
        ) {
            return $return_type;
        }

        $return_type->addType(new Type\Atomic\TNull);
        $return_type->ignore_nullable_issues = true;

        return $return_type;
    }
}
