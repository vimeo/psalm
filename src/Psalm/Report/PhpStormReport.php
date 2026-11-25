<?php

declare(strict_types=1);

namespace Psalm\Report;

use Override;
use Psalm\Internal\Analyzer\DataFlowNodeData;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Report\ConsoleIssueFormatter;
use Psalm\Report;

/**
 * @psalm-external-mutation-free
 * @api
 */
final class PhpStormReport extends Report
{
    use ConsoleIssueFormatter;

    #[Override]
    public function create(): string
    {
        $output = '';
        foreach ($this->issues_data as $issue_data) {
            $output .= $this->formatIssue($issue_data) . "\n" . "\n";
        }

        return $output;
    }

    /**
     * @psalm-mutation-free
     */
    private function formatIssueHeader(IssueData $issue_data, string $level_label, string $message): string
    {
        return $this->getSeverityLabel($issue_data) . $level_label . ': ' . $issue_data->type
            . "\nat " . $this->getFileReference($issue_data)
            . "\n" . $message . "\n";
    }

    /**
     * PhpStorm turns absolute paths into links.
     *
     * @psalm-mutation-free
     */
    private function getFileReference(IssueData|DataFlowNodeData $data): string
    {
        return $this->escapeControlCharacters($data->file_path) . ':' . $data->line_from . ':' . $data->column_from;
    }
}
