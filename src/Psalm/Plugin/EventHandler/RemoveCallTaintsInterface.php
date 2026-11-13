<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\AddRemoveCallTaintsEvent;

/**
 * @api
 */
interface RemoveCallTaintsInterface
{
    /**
     * Called to see what taints an argument going into a call, or what a call returns, loses
     */
    public static function removeCallTaints(AddRemoveCallTaintsEvent $event): int;
}
