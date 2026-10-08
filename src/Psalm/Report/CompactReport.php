<?php

declare(strict_types=1);

namespace Psalm\Report;

use Override;
use Psalm\Report;

/**
 * @psalm-external-mutation-free
 * @api
 */
final class CompactReport extends Report
{
    /**
     * @psalm-mutation-free
     */
    #[Override]
    public function create(): string
    {
        $output = '';

        foreach ($this->issues_data as $issue_data) {
            $severity = $this->getSeverityLabel($issue_data);

            $output .= $severity . ' ' . $issue_data->file_name . ':' . $issue_data->line_from
                . ':' . $issue_data->column_from . ' ' . $issue_data->type . ': ' . $issue_data->message . "\n";
        }

        return $output;
    }
}
