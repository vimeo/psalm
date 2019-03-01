<?php

namespace Psalm\Test\Plugin\Hook;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Type;
use Psalm\StatementsSource;
use Psalm\Plugin\Hook\{
    MethodExistenceProviderInterface,
    MethodVisibilityProviderInterface,
    MethodParamsProviderInterface,
    MethodReturnTypeProviderInterface
};

class FooMethodProvider implements
    MethodExistenceProviderInterface,
    MethodVisibilityProviderInterface,
    MethodParamsProviderInterface,
    MethodReturnTypeProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getClassLikeNames() : array
    {
        return ['Ns\Foo'];
    }

    /**
     * @return ?bool
     */
    public static function doesMethodExist(
        string $fq_classlike_name,
        string $method_name_lc,
        StatementsSource $source = null,
        CodeLocation $code_location = null
    ) {
        return $method_name_lc === 'magicmethod';
    }

    /**
     * @return ?bool
     */
    public static function isMethodVisible(
        StatementsSource $source,
        string $fq_classlike_name,
        string $method_name,
        Context $context = null,
        CodeLocation $code_location = null
    ) {
        return true;
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     * @return ?array<int, \Psalm\Storage\FunctionLikeParameter>
     */
    public static function getMethodParams(
        string $fq_classlike_name,
        string $method_name_lowercase,
        array $call_args = null,
        StatementsSource $statements_source = null,
        Context $context = null,
        CodeLocation $code_location = null
    ) {
        return [new \Psalm\Storage\FunctionLikeParameter('first', false, Type::getString())];
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     * @return ?Type\Union
     */
    public static function getMethodReturnType(
        StatementsSource $source,
        string $fq_classlike_name,
        string $method_name,
        array $call_args,
        Context $context,
        CodeLocation $code_location
    ) {
        return Type::getString();
    }
}
