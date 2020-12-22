<?php
namespace Psalm\Plugin\Hook;

use Psalm\Plugin\Hook\Event\PropertyTypeProviderEvent;
use Psalm\Type;

interface PropertyTypeProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getClassLikeNames() : array;

    public static function getPropertyType(PropertyTypeProviderEvent $event): ?Type\Union;
}
