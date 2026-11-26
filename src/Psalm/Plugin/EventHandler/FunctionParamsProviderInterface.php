<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\FunctionParamsProviderEvent;
use Psalm\Storage\FunctionLikeParameter;

/**
 * @api
 */
interface FunctionParamsProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array;

    /**
     * Return null to keep the parameters Psalm already knows for the function, and an empty array when the
     * function takes no parameter.
     *
     * @return ?array<int, FunctionLikeParameter>
     */
    public static function getFunctionParams(FunctionParamsProviderEvent $event): ?array;
}
