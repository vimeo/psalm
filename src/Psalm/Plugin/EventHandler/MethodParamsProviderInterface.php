<?php
namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\MethodParamsProviderEvent;

interface MethodParamsProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getClassLikeNames() : array;

    /**
     * @return ?array<int, \Psalm\Storage\FunctionLikeParameter>
     */
    public static function getMethodParams(MethodParamsProviderEvent $event): ?array;
}
