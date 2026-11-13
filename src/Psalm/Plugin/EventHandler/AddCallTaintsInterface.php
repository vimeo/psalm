<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\AddRemoveCallTaintsEvent;

/**
 * @api
 */
interface AddCallTaintsInterface
{
    /**
     * Called to see what taints an argument going into a call, or what a call returns, takes
     */
    public static function addCallTaints(AddRemoveCallTaintsEvent $event): int;
}
