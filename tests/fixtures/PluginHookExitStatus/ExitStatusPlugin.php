<?php

use Psalm\Plugin\EventHandler\AfterFileAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterFileAnalysisEvent;

class ExitStatusPlugin implements AfterFileAnalysisInterface
{
    public static function afterAnalyzeFile(AfterFileAnalysisEvent $event): void {
        // The following causes a zero exit status, which we explicitly want to ensure does NOT result in a zero exit status from Psalm itself.
        die('Example output from failing hook');
    }
}
