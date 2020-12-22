<?php
namespace Psalm\Plugin\Hook;

use Psalm\Plugin\Hook\Event\AfterAnalysisEvent;

interface AfterAnalysisInterface
{
    /**
     * Called after analysis is complete
     */
    public static function afterAnalysis(AfterAnalysisEvent $event): void;
}
