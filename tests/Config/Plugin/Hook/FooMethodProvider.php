<?php
namespace Psalm\Test\Config\Plugin\Hook;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Plugin\Hook\MethodExistenceProviderInterface;
use Psalm\Plugin\Hook\MethodParamsProviderInterface;
use Psalm\Plugin\Hook\MethodReturnTypeProviderInterface;
use Psalm\StatementsSource;
use Psalm\Type;

class FooMethodProvider implements
    MethodExistenceProviderInterface,
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

    public static function doesMethodExist(
        string $fq_classlike_name,
        string $method_name_lowercase,
        ?StatementsSource $source = null,
        ?CodeLocation $code_location = null
    ): ?bool {
        if ($method_name_lowercase === 'magicmethod' || $method_name_lowercase === 'magicmethod2') {
            return true;
        }

        return null;
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     *
     * @return ?array<int, \Psalm\Storage\FunctionLikeParameter>
     */
    public static function getMethodParams(
        string $fq_classlike_name,
        string $method_name_lowercase,
        ?array $call_args = null,
        ?StatementsSource $statements_source = null,
        ?Context $context = null,
        ?CodeLocation $code_location = null
    ): ?array {
        if ($method_name_lowercase === 'magicmethod' || $method_name_lowercase === 'magicmethod2') {
            return [new \Psalm\Storage\FunctionLikeParameter('first', false, Type::getString())];
        }

        return null;
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     *
     */
    public static function getMethodReturnType(
        StatementsSource $source,
        string $fq_classlike_name,
        string $method_name_lowercase,
        array $call_args,
        Context $context,
        CodeLocation $code_location,
        ?array $template_type_parameters = null,
        ?string $called_fq_classlike_name = null,
        ?string $called_method_name_lowercase = null
    ): ?Type\Union {
        if ($method_name_lowercase === 'magicmethod') {
            return Type::getString();
        } else {
            return new \Psalm\Type\Union([new \Psalm\Type\Atomic\TNamedObject('NS\\Foo2')]);
        }
    }
}
