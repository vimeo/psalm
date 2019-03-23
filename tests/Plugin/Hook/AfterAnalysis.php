<?php

namespace Psalm\Test\Plugin\Hook;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Type;
use Psalm\StatementsSource;
use Psalm\Plugin\Hook\{
    AfterAnalysisInterface
};
use Psalm\SourceControl\SourceControlInfo;

class AfterAnalysis implements
    AfterAnalysisInterface
{
    /**
     * Called after analysis is complete
     * @param array<int, array{severity: string, line_from: int, line_to: int, type: string, message: string,
     * file_name: string, file_path: string, snippet: string, from: int, to: int,
     * snippet_from: int, snippet_to: int, column_from: int, column_to: int, selected_text: string}> $issues
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
