<?php
namespace Psalm\Test\Config\Plugin\Hook;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Plugin\Hook\{
    AfterAnalysisInterface
};
use Psalm\SourceControl\SourceControlInfo;

class AfterAnalysis implements
    AfterAnalysisInterface
{
    /**
     * Called after analysis is complete
     *
     * @param array<string, list<IssueData>> $issues
     */
    public static function afterAnalysis(
        Codebase $codebase,
        array $issues,
        array $build_info,
        ?SourceControlInfo $source_control_info = null
    ): void {
        if ($source_control_info) {
            $source_control_info->toArray();
        }
    }
}
