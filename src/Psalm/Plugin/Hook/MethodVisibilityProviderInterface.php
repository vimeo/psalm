<?php
namespace Psalm\Plugin\Hook;

use Psalm\Plugin\Hook\Event\MethodVisibilityProviderEvent;

interface MethodVisibilityProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getClassLikeNames() : array;

    public static function isMethodVisible(MethodVisibilityProviderEvent $event): ?bool;
}
