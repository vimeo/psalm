<?php

declare(strict_types=1);

namespace Psalm\Test\Config\Plugin\Hook;

use Override;
use Psalm\Plugin\EventHandler\Event\FunctionExistenceProviderEvent;
use Psalm\Plugin\EventHandler\Event\FunctionParamsProviderEvent;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionExistenceProviderInterface;
use Psalm\Plugin\EventHandler\FunctionParamsProviderInterface;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\StrId;
use Psalm\Type;
use Psalm\Type\Union;

/**
 * @psalm-suppress UnusedClass registered as a plugin via test config, instantiated by reflection
 */
final class MagicFunctionProvider implements
    FunctionExistenceProviderInterface,
    FunctionParamsProviderInterface,
    FunctionReturnTypeProviderInterface
{
    /**
     * @return array<int>
     * @psalm-pure
     */
    #[Override]
    public static function getFunctionIds(): array
    {
        return [StrId::magicFunction];
    }

    /**
     * @psalm-mutation-free
     */
    #[Override]
    public static function doesFunctionExist(FunctionExistenceProviderEvent $event): ?bool
    {
        $function_id = $event->getFunctionId();
        return $function_id === StrId::magicFunction;
    }

    /**
     * @return ?array<int, FunctionLikeParameter>
     */
    #[Override]
    public static function getFunctionParams(FunctionParamsProviderEvent $event): ?array
    {
        return [new FunctionLikeParameter(StrId::first, false, Type::getString(), Type::getString())];
    }

    #[Override]
    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        return Type::getString();
    }
}
