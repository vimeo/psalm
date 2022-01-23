<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\FunctionDynamicStorageProviderEvent;
use Psalm\Storage\FunctionStorage;

interface FunctionDynamicStorageProviderInterface
{
    /**
     * @return array<lowercase-string>
     */
    public static function getFunctionIds(): array;

    public static function getFunctionStorage(FunctionDynamicStorageProviderEvent $event): ?FunctionStorage;
}
