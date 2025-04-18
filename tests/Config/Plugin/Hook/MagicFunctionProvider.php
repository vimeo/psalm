<?php

declare(strict_types=1);

namespace Psalm\Test\Config\Plugin\Hook;

use Psalm\Plugin\EventHandler\Event\FunctionExistenceProviderEvent;
use Psalm\Plugin\EventHandler\Event\FunctionParamsProviderEvent;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionExistenceProviderInterface;
use Psalm\Plugin\EventHandler\FunctionParamsProviderInterface;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Union;

class MagicFunctionProvider implements
    FunctionExistenceProviderInterface,
    FunctionParamsProviderInterface,
    FunctionReturnTypeProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return ['magicfunction'];
    }

    public static function doesFunctionExist(FunctionExistenceProviderEvent $event): ?bool
    {
        $function_id = $event->getFunctionId();
        return $function_id === 'magicfunction';
    }

    /**
     * @return ?array<int, FunctionLikeParameter>
     */
    public static function getFunctionParams(FunctionParamsProviderEvent $event): ?array
    {
        return [new FunctionLikeParameter('first', false, Type::getString(), Type::getString())];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        return Type::getString();
    }
}
