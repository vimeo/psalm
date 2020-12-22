<?php
namespace Psalm\Test\Config\Plugin\Hook;

use PhpParser;
use Psalm\Plugin\Hook\MethodExistenceProviderInterface;
use Psalm\Plugin\Hook\MethodParamsProviderInterface;
use Psalm\Plugin\Hook\MethodReturnTypeProviderInterface;
use Psalm\Plugin\Hook\Event\MethodExistenceProviderEvent;
use Psalm\Plugin\Hook\Event\MethodParamsProviderEvent;
use Psalm\Plugin\Hook\Event\MethodReturnTypeProviderEvent;
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

    public static function doesMethodExist(MethodExistenceProviderEvent $event): ?bool {
        $method_name_lowercase = $event->getMethodNameLowercase();
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
    public static function getMethodParams(MethodParamsProviderEvent $event): ?array {
        $method_name_lowercase = $event->getMethodNameLowercase();
        if ($method_name_lowercase === 'magicmethod' || $method_name_lowercase === 'magicmethod2') {
            return [new \Psalm\Storage\FunctionLikeParameter('first', false, Type::getString())];
        }

        return null;
    }

    /**
     * @param  array<PhpParser\Node\Arg>    $call_args
     *
     */
    public static function getMethodReturnType(MethodReturnTypeProviderEvent $event): ?Type\Union {
        $method_name_lowercase = $event->getMethodNameLowercase();
        if ($method_name_lowercase === 'magicmethod') {
            return Type::getString();
        } else {
            return new \Psalm\Type\Union([new \Psalm\Type\Atomic\TNamedObject('NS\\Foo2')]);
        }
    }
}
