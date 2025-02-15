<?php

declare(strict_types=1);

namespace Psalm\Tests\Config\Plugin\EventHandler\RemoveTaints;

use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Plugin\EventHandler\RemoveTaintsInterface;
use Psalm\Type\TaintKindGroup;

/**
 * @psalm-suppress UnusedClass
 */
class RemoveAllTaintsPlugin implements RemoveTaintsInterface
{
    /**
     * Called to see what taints should be removed
     *
     * @return list<string>
     */
    public static function removeTaints(AddRemoveTaintsEvent $event): array
    {
        return TaintKindGroup::ALL_INPUT;
    }
}
