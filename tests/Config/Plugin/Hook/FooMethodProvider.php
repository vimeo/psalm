<?php

declare(strict_types=1);

namespace Psalm\Test\Config\Plugin\Hook;

use Psalm\Plugin\EventHandler\Event\MethodExistenceProviderEvent;
use Psalm\Plugin\EventHandler\Event\MethodParamsProviderEvent;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\MethodExistenceProviderInterface;
use Psalm\Plugin\EventHandler\MethodParamsProviderInterface;
use Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

class FooMethodProvider implements
    MethodExistenceProviderInterface,
    MethodParamsProviderInterface,
    MethodReturnTypeProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getClassLikeNames(): array
    {
        return ['Ns\Foo'];
    }

    public static function doesMethodExist(MethodExistenceProviderEvent $event): ?bool
    {
        $method_name_lowercase = $event->getMethodNameLowercase();
        if ($method_name_lowercase === 'magicmethod' || $method_name_lowercase === 'magicmethod2') {
            return true;
        }

        return null;
    }

    /**
     * @return ?array<int, FunctionLikeParameter>
     */
    public static function getMethodParams(MethodParamsProviderEvent $event): ?array
    {
        $method_name_lowercase = $event->getMethodNameLowercase();
        if ($method_name_lowercase === 'magicmethod' || $method_name_lowercase === 'magicmethod2') {
            return [new FunctionLikeParameter('first', false, Type::getString(), Type::getString())];
        }

        return null;
    }

    public static function getMethodReturnType(MethodReturnTypeProviderEvent $event): ?Union
    {
        $method_name_lowercase = $event->getMethodNameLowercase();
        if ($method_name_lowercase === 'magicmethod') {
            return Type::getString();
        } else {
            return new Union([new TNamedObject('NS\\Foo2')]);
        }
    }
}
