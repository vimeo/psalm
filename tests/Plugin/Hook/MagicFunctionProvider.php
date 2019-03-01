<?php

namespace Psalm\Test\Plugin\Hook;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Type;
use Psalm\StatementsSource;
use Psalm\Plugin\Hook\{
    FunctionExistenceProviderInterface,
    FunctionVisibilityProviderInterface,
    FunctionParamsProviderInterface,
    FunctionReturnTypeProviderInterface
};

class MagicFunctionProvider implements
    FunctionExistenceProviderInterface,
    FunctionParamsProviderInterface,
    FunctionReturnTypeProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getFunctionIds() : array
    {
        return ['magicfunction'];
    }

    /**
     * @return ?bool
     */
    public static function doesFunctionExist(
        StatementsSource $source,
        string $function_id,
        CodeLocation $code_location = null
    ) {
        return $function_id === 'magicfunction';
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     * @return ?array<int, \Psalm\Storage\FunctionLikeParameter>
     */
    public static function getFunctionParams(
        StatementsSource $statements_source,
        string $function_id,
        array $call_args,
        Context $context = null,
        CodeLocation $code_location = null
    ) {
        return [new \Psalm\Storage\FunctionLikeParameter('first', false, Type::getString())];
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     * @return ?Type\Union
     */
    public static function getFunctionReturnType(
        StatementsSource $source,
        string $function_id,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ) {
        return Type::getString();
    }
}
