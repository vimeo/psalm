<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\AfterAnalysisEvent;

interface AfterAnalysisInterface
{
    /**
     * Called after analysis is complete
     */
    public static function afterAnalysis(AfterAnalysisEvent $event): void;
}
