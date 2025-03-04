<?php

declare(strict_types=1);

namespace Psalm\Tests\Config\Plugin\EventHandler\RemoveTaints;

use Override;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Plugin\EventHandler\RemoveTaintsInterface;
use Psalm\Type\TaintKind;

/**
 * @psalm-suppress UnusedClass
 */
final class RemoveAllTaintsPlugin implements RemoveTaintsInterface
{
    /**
     * Called to see what taints should be removed
     */
    #[Override]
    public static function removeTaints(AddRemoveTaintsEvent $event): int
    {
        return TaintKind::ALL_INPUT;
    }
}
