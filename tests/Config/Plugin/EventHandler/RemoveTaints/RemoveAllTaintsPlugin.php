<?php

declare(strict_types=1);

namespace Psalm\Tests\Config\Plugin\EventHandler\RemoveTaints;

use Override;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Plugin\EventHandler\RemoveTaintsInterface;
use Psalm\Type\TaintKindGroup;

/**
 * @psalm-suppress UnusedClass
 */
final class RemoveAllTaintsPlugin implements RemoveTaintsInterface
{
    /**
     * Called to see what taints should be removed
     *
     * @return list<string>
     */
    #[Override]
    public static function removeTaints(AddRemoveTaintsEvent $event): array
    {
        return TaintKindGroup::ALL_INPUT;
    }
}
