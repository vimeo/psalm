<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\DynamicFunctionStorage;
use Psalm\Plugin\EventHandler\Event\DynamicFunctionStorageProviderEvent;

/**
 * @api
 */
interface DynamicFunctionStorageProviderInterface
{
    /**
     * @return array<int> interned function ids (casing is irrelevant, they are lowercased on registration)
     */
    public static function getFunctionIds(): array;

    public static function getFunctionStorage(DynamicFunctionStorageProviderEvent $event): ?DynamicFunctionStorage;
}
