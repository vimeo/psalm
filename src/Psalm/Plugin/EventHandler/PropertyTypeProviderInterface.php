<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\PropertyTypeProviderEvent;
use Psalm\Type\Union;

/**
 * @api
 */
interface PropertyTypeProviderInterface
{
    /**
     * @return array<int> interned class names, with their exact declared casing (names are case-sensitive)
     */
    public static function getClassLikeNames(): array;

    public static function getPropertyType(PropertyTypeProviderEvent $event): ?Union;
}
