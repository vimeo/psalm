<?php
namespace Psalm\Plugin\Hook;

use Psalm\Plugin\Hook\Event\AfterFileAnalysisEvent;

interface AfterFileAnalysisInterface
{
    /**
     * Called after a file has been checked
     */
    public static function afterAnalyzeFile(AfterFileAnalysisEvent $event): void;
}
