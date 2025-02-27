<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;

interface RemoveTaintsInterface
{
    /**
     * Called to see what taints should be removed
     */
    public static function removeTaints(AddRemoveTaintsEvent $event): int;
}
