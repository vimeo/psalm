<?php
namespace Psalm\Test\Config\Plugin\Hook;

use PhpParser;
use Psalm\Plugin\Hook\FunctionExistenceProviderInterface;
use Psalm\Plugin\Hook\FunctionParamsProviderInterface;
use Psalm\Plugin\Hook\FunctionReturnTypeProviderInterface;
use Psalm\Plugin\Hook\Event\FunctionExistenceProviderEvent;
use Psalm\Plugin\Hook\Event\FunctionParamsProviderEvent;
use Psalm\Plugin\Hook\Event\FunctionReturnTypeProviderEvent;
use Psalm\Type;

class MagicFunctionProvider implements
    FunctionExistenceProviderInterface,
    FunctionParamsProviderInterface,
    FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds() : array
    {
        return ['magicfunction'];
    }

    public static function doesFunctionExist(FunctionExistenceProviderEvent $event): ?bool {
        $function_id = $event->getFunctionId();
        return $function_id === 'magicfunction';
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     *
     * @return ?array<int, \Psalm\Storage\FunctionLikeParameter>
     */
    public static function getFunctionParams(FunctionParamsProviderEvent $event): ?array {
        return [new \Psalm\Storage\FunctionLikeParameter('first', false, Type::getString())];
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     *
     */
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Type\Union {
        return Type::getString();
    }
}
