<?php

declare(strict_types=1);

namespace Psalm\Test\Config\Plugin\Hook;

use Override;
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

/**
 * @psalm-suppress UnusedClass registered as a plugin via test config, instantiated by reflection
 */
final class FooMethodProvider implements
    MethodExistenceProviderInterface,
    MethodParamsProviderInterface,
    MethodReturnTypeProviderInterface
{
    /**
     * @return array<string>
     * @psalm-pure
     */
    #[Override]
    public static function getClassLikeNames(): array
    {
        return ['Ns\Foo'];
    }

    /**
     * @psalm-mutation-free
     */
    #[Override]
    public static function doesMethodExist(MethodExistenceProviderEvent $event): ?bool
    {
        $method_name_lowercase = $event->getMethodNameLowercase();
        if ($method_name_lowercase === 'magicmethod'
            || $method_name_lowercase === 'magicmethod2'
            || $method_name_lowercase === 'sourcedmagicmethod'
            || $method_name_lowercase === 'paramlessmagicmethod'
        ) {
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
        $method_name_lowercase = $event->getMethodNameLowercase();
        if ($method_name_lowercase === 'magicmethod' || $method_name_lowercase === 'magicmethod2') {
            return [new FunctionLikeParameter('first', false, Type::getString(), Type::getString())];
        }

        // Mirrors providers that need the codebase, which they reach through the statements source.
        if (($method_name_lowercase === 'sourcedmagicmethod' || $method_name_lowercase === 'sourcedrealmethod')
            && $event->getStatementsSource() !== null
        ) {
            return [new FunctionLikeParameter('first', false, Type::getInt(), Type::getInt())];
        }

        return null;
    }

    #[Override]
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
