<?php

declare(strict_types=1);

namespace Psalm\Report;

use Override;
use Psalm\Internal\Report\ConsoleIssueFormatter;
use Psalm\Report;

/**
 * @psalm-external-mutation-free
 * @api
 */
final class ConsoleReport extends Report
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
}
