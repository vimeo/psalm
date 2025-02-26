<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Type\TaintKind;

interface AddTaintsInterface
{
    /**
     * Called to see what taints should be added
     *
     * @return int-mask-of<TaintKind::*>
     */
    public static function addTaints(AddRemoveTaintsEvent $event): int;
}
