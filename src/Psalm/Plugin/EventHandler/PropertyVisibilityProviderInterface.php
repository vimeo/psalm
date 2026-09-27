<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\PropertyVisibilityProviderEvent;

/**
 * @api
 */
interface PropertyVisibilityProviderInterface
{
    /**
     * @return array<int> interned class names, with their exact declared casing (names are case-sensitive)
     */
    public static function getClassLikeNames(): array;

    public static function isPropertyVisible(PropertyVisibilityProviderEvent $event): ?bool;
}
