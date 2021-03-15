<?php
namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\ShouldTaintEvent;

interface ShouldTaintInterface
{
    /**
     * Called to see if a statement should be tainted.
     *
     * @return bool
     */
    public static function shouldTaint(ShouldTaintEvent $event): bool;
}
