<?php

namespace Psalm\Report;

use Psalm\Config;
use Psalm\Report;

use function sprintf;

final class EmacsReport extends Report
{
    public function create(): string
    {
        $output = '';
        foreach ($this->issues_data as $issue_data) {
            $output .= sprintf(
                '%s:%s:%s:%s - %s: %s (see %s)',
                $issue_data->file_path,
                $issue_data->line_from,
                $issue_data->column_from,
                ($issue_data->severity === Config::REPORT_ERROR ? 'error' : 'warning'),
                $issue_data->type,
                $issue_data->message,
                $issue_data->link,
            ) . "\n";
        }

        return $output;
    }
}
