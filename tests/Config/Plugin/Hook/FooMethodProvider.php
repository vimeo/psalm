<?php

declare(strict_types=1);

namespace Psalm\Test\Config\Plugin\Hook;

use Override;
use Psalm\Interner;
use Psalm\Plugin\EventHandler\Event\MethodExistenceProviderEvent;
use Psalm\Plugin\EventHandler\Event\MethodParamsProviderEvent;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\MethodExistenceProviderInterface;
use Psalm\Plugin\EventHandler\MethodParamsProviderInterface;
use Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\StrId;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

/**
 * @psalm-suppress UnusedClass registered as a plugin via test config, instantiated by reflection
 */
final class FooMethodProvider implements
    MethodExistenceProviderInterface,
    MethodParamsProviderInterface,
    MethodReturnTypeProviderInterface
{
    /**
     * @return array<int>
     * @psalm-pure
     */
    #[Override]
    public static function getClassLikeNames(): array
    {
        return [Interner::intern('Ns\Foo')];
    }

    /**
     * @psalm-mutation-free
     */
    #[Override]
    public static function doesMethodExist(MethodExistenceProviderEvent $event): ?bool
    {
        $method_name = $event->getMethodName();
        if ($method_name === StrId::magicMethod || $method_name === StrId::magicMethod2) {
            return true;
        }

        return null;
    }

    /**
     * @return ?array<int, FunctionLikeParameter>
     */
    #[Override]
    public static function getMethodParams(MethodParamsProviderEvent $event): ?array
    {
        $method_name = $event->getMethodName();
        if ($method_name === StrId::magicMethod || $method_name === StrId::magicMethod2) {
            return [new FunctionLikeParameter(StrId::first, false, Type::getString(), Type::getString())];
        }

        return null;
    }

    #[Override]
    public static function getMethodReturnType(MethodReturnTypeProviderEvent $event): ?Union
    {
        $method_name = $event->getMethodName();
        if ($method_name === StrId::magicMethod) {
            return Type::getString();
        } else {
            return new Union([new TNamedObject(Interner::intern('Ns\\Foo2'))]);
        }
    }
}
