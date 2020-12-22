<?php
namespace Psalm\Test\Config\Plugin\Hook;

use Psalm\Internal\Analyzer\IssueData;
use Psalm\Plugin\Hook\{AfterAnalysisInterface, Event\AfterAnalysisEvent};

class AfterAnalysis implements
    AfterAnalysisInterface
{
    /**
     * Called after analysis is complete
     *
     * @param array<string, list<IssueData>> $issues
     */
    public static function afterAnalysis(AfterAnalysisEvent $event): void
    {
        $source_control_info = $event->getSourceControlInfo();
        if ($source_control_info) {
            $source_control_info->toArray();
        }
    }
}
