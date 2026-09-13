<?php

declare(strict_types=1);

namespace Psalm\Test\Config\Plugin\Hook;

use Override;
use Psalm\Plugin\EventHandler\AfterAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterAnalysisEvent;

/**
 * @psalm-immutable
 * @psalm-suppress UnusedClass registered as a plugin via test config, instantiated by reflection
 */
final class AfterAnalysis implements AfterAnalysisInterface
{
    /**
     * Called after analysis is complete
     *
     * @psalm-mutation-free
     */
    #[Override]
    public static function afterAnalysis(AfterAnalysisEvent $event): void
    {
        $source_control_info = $event->getSourceControlInfo();
        if ($source_control_info) {
            $source_control_info->toArray();
        }
    }
}
