<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Type\TaintKind;

interface RemoveTaintsInterface
{
    /**
     * Called to see what taints should be removed
     *
     * @return int-mask-of<TaintKind::*>
     */
    public static function removeTaints(AddRemoveTaintsEvent $event): int;
}
