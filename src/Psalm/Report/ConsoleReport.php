<?php
namespace Psalm\Report;

use Psalm\Config;
use Psalm\Report;
use function substr;

class ConsoleReport extends Report
{
    /**
     * {@inheritdoc}
     */
    public function create(): string
    {
        $output = '';
        foreach ($this->issues_data as $issue_data) {
            $output .= $this->format($issue_data) . "\n" . "\n";
        }

        return $output;
    }

    private function format(\Psalm\Internal\Analyzer\IssueData $issue_data): string
    {
        $issue_string = '';

        $is_error = $issue_data->severity === Config::REPORT_ERROR;

        if ($is_error) {
            $issue_string .= ($this->use_color ? "\e[0;31mERROR\e[0m" : 'ERROR');
        } else {
            $issue_string .= 'INFO';
        }

        $issue_reference = ' (see ' . $issue_data->link . ')';

        $issue_string .= ': ' . $issue_data->type
            . ' - ' . $issue_data->file_name . ':' . $issue_data->line_from . ':' . $issue_data->column_from
            . ' - ' . $issue_data->message . $issue_reference . "\n";

        if ($this->show_snippet) {
            $snippet = $issue_data->snippet;

            if (!$this->use_color) {
                $issue_string .= $snippet;
            } else {
                $selection_start = $issue_data->from - $issue_data->snippet_from;
                $selection_length = $issue_data->to - $issue_data->from;

                $issue_string .= substr($snippet, 0, $selection_start)
                    . ($is_error ? "\e[97;41m" : "\e[30;47m") . substr($snippet, $selection_start, $selection_length)
                    . "\e[0m" . substr($snippet, $selection_length + $selection_start) . "\n";
            }
        }

        return $issue_string;
    }
}
