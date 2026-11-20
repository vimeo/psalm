<?php

declare(strict_types=1);

namespace Psalm\Report;

use Override;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Report\ConsoleIssueFormatter;
use Psalm\Report;

use function usort;

/**
 * @psalm-external-mutation-free
 * @api
 */
final class ByIssueLevelAndTypeReport extends Report
{
    use ConsoleIssueFormatter;

    #[Override]
    public function create(): string
    {
        $this->sortIssuesByLevelAndType();

        $output = <<<HEADING
|----------------------------------------------------------------------------------------|
|    Issues have been sorted by level and type. Feature-specific issues and the          |
|    most serious issues that will always be reported are listed first, with             |
|    remaining issues in level order. Issues near the top are usually the most serious.  |
|    Reducing the errorLevel in psalm.xml will suppress output of issues further down    |
|    this report.                                                                        |
|                                                                                        |
|    The level at which issue is reported as an error is given in brackets - e.g.        |
|    `ERROR (2): MissingReturnType` indicates that MissingReturnType is only reported    |
|    as an error when Psalm's level is set to 2 or below.                                |
|                                                                                        |
|    Issues are shown or hidden in this report according to current settings. For        |
|    the most complete report set Psalm's error level to 1 or use --show-info=true       |
|    See https://psalm.dev/docs/running_psalm/error_levels/                              |
|----------------------------------------------------------------------------------------|


HEADING;

        foreach ($this->issues_data as $issue_data) {
            $level = $issue_data->error_level;
            $output .= $this->formatIssue($issue_data, $level > 0 ? " ($level)" : '') . "\n" . "\n";
        }

        return $output;
    }

    /**
     * @psalm-external-mutation-free
     */
    private function sortIssuesByLevelAndType(): void
    {
        usort(
            $this->issues_data,
            static fn(IssueData $left, IssueData $right): int => [$left->error_level > 0, -$left->error_level,
                    $left->type, $left->file_path, $left->file_name, $left->line_from]
                <=> [$right->error_level > 0, -$right->error_level, $right->type, $right->file_path, $right->file_name,
                    $right->line_from],
        );
    }
}
