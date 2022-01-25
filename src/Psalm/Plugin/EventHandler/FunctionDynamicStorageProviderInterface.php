<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\DynamicFunctionStorage;
use Psalm\Plugin\EventHandler\Event\FunctionDynamicStorageProviderEvent;

interface FunctionDynamicStorageProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array;

    public static function getFunctionStorage(FunctionDynamicStorageProviderEvent $event): ?DynamicFunctionStorage;
}
