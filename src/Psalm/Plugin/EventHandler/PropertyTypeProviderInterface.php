<?php
namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\PropertyTypeProviderEvent;
use Psalm\Type;

interface PropertyTypeProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getClassLikeNames() : array;

    public static function getPropertyType(PropertyTypeProviderEvent $event): ?Type\Union;
}
