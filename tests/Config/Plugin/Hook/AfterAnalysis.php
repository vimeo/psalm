<?php
namespace Psalm\Test\Config\Plugin\Hook;

use Psalm\Codebase;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Plugin\Hook\{
    AfterAnalysisInterface
};
use Psalm\SourceControl\SourceControlInfo;
use Psalm\Type;

class AfterAnalysis implements
    AfterAnalysisInterface
{
    /**
     * Called after analysis is complete
     *
     * @param array<string, list<IssueData>> $issues
     *
     * @return void
     */
    public static function afterAnalysis(
        Codebase $codebase,
        array $issues,
        array $build_info,
        SourceControlInfo $source_control_info = null
    ) {
        if ($source_control_info) {
            $source_control_info->toArray();
        }
    }
}
