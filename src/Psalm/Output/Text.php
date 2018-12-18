<?php
namespace Psalm\Output;

use Psalm\Config;
use Psalm\Output;

class Text extends Output
{
    /**
     * {{@inheritdoc}}
     */
    public function create(): string
    {
        $output = '';
        foreach ($this->issues_data as $issue_data) {
            $output .= sprintf(
                '%s:%s:%s:%s - %s: %s',
                $issue_data['file_path'],
                $issue_data['line_from'],
                $issue_data['column_from'],
                ($issue_data['severity'] === Config::REPORT_ERROR ? 'error' : 'warning'),
                $issue_data['type'],
                $issue_data['message']
            ) . "\n";
        }

        return $output;
    }
}
