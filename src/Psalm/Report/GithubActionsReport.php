<?php
namespace Psalm\Report;

use Psalm\Config;
use Psalm\Report;

use function sprintf;

class GithubActionsReport extends Report
{
    public function create(): string
    {
        $output = '';
        foreach ($this->issues_data as $issue_data) {
            $issue_reference = $issue_data->link ? ' (see ' . $issue_data->link . ')' : '';
            $output .= sprintf(
                '::%1$s file=%2$s,line=%3$s,col=%4$s,title=%5$s::%2$s:%3$s:%4$s: %5$s: %6$s',
                ($issue_data->severity === Config::REPORT_ERROR ? 'error' : 'warning'),
                $issue_data->file_name,
                $issue_data->line_from,
                $issue_data->column_from,
                $issue_data->type,
                $issue_data->message . $issue_reference
            ) . "\n";
        }

        return $output;
    }
}
