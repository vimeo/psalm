<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\MethodParamsProviderEvent;
use Psalm\Storage\FunctionLikeParameter;

/**
 * @api
 */
interface MethodParamsProviderInterface
{
    /**
     * @return array<int> interned class names, with their exact declared casing (names are case-sensitive)
     */
    public static function getClassLikeNames(): array;

    /**
     * @return ?array<int, FunctionLikeParameter>
     */
    public static function getMethodParams(MethodParamsProviderEvent $event): ?array;
}
