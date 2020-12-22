<?php
namespace Psalm\Plugin\Hook;

use Psalm\Plugin\Hook\Event\PropertyVisibilityProviderEvent;

interface PropertyVisibilityProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getClassLikeNames() : array;

    public static function isPropertyVisible(PropertyVisibilityProviderEvent $event): ?bool;
}
