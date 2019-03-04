<?php

namespace Psalm\Internal\Provider\ReturnTypeProvider;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type;

class DomNodeAppendChild implements \Psalm\Plugin\Hook\MethodReturnTypeProviderInterface
{
    public static function getClassLikeNames() : array
    {
        return ['DomNode'];
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     * @return ?Type\Union
     */
    public static function getMethodReturnType(
        StatementsSource $source,
        string $fq_classlike_name,
        string $method_name_lowercase,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ) {
        if ($method_name_lowercase === 'appendchild'
            && isset($call_args[0]->value->inferredType)
            && $call_args[0]->value->inferredType->hasObjectType()
        ) {
            return clone $call_args[0]->value->inferredType;
        }
    }
}
