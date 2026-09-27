<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\MethodVisibilityProviderEvent;

/**
 * @api
 */
interface MethodVisibilityProviderInterface
{
    /**
     * @return array<int> interned class names (casing is irrelevant, they are lowercased on registration)
     */
    public static function getClassLikeNames(): array;

    public static function isMethodVisible(MethodVisibilityProviderEvent $event): ?bool;
}
