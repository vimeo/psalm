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
     * @return array<string>
     */
    public static function getClassLikeNames(): array;

    /**
     * Return null to keep the parameters Psalm already knows for the method, and an empty array when the method
     * takes no parameter.
     *
     * @return ?array<int, FunctionLikeParameter>
     */
    public static function getMethodParams(MethodParamsProviderEvent $event): ?array;
}
