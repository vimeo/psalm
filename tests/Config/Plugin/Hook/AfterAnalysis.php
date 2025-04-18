<?php

declare(strict_types=1);

namespace Psalm\Test\Config\Plugin\Hook;

use Psalm\Plugin\EventHandler\AfterAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterAnalysisEvent;

class AfterAnalysis implements AfterAnalysisInterface
{
    /**
     * Called after analysis is complete
     */
    public static function afterAnalysis(AfterAnalysisEvent $event): void
    {
        $source_control_info = $event->getSourceControlInfo();
        if ($source_control_info) {
            $source_control_info->toArray();
        }
    }
}
