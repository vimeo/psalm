<?php
namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\FunctionParamsProviderEvent;

interface FunctionParamsProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds() : array;

    /**
     * @return ?array<int, \Psalm\Storage\FunctionLikeParameter>
     */
    public static function getFunctionParams(FunctionParamsProviderEvent $event): ?array;
}
