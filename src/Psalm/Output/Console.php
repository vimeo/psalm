<?php
namespace Psalm\Output;

use Psalm\Config;
use Psalm\Output;

class Console extends Output
{
    /**
     * {{@inheritdoc}}
     */
    public function create(): string
    {
        $output = '';
        foreach ($this->issues_data as $issue_data) {
            if (!$this->show_info && $issue_data['severity'] === Config::REPORT_INFO) {
                continue;
            }

            $output .= $this->format($issue_data) . "\n" . "\n";
        }

        return $output;
    }

    /**
     * @param  array{severity: string, line_from: int, line_to: int, type: string, message: string,
     *  file_name: string, file_path: string, snippet: string, from: int, to: int,
     *  snippet_from: int, snippet_to: int, column_from: int, column_to: int} $issue_data
     *
     * @return string
     */
    private function format(array $issue_data): string
    {
        $issue_string = '';

        $is_error = $issue_data['severity'] === Config::REPORT_ERROR;

        if ($is_error) {
            $issue_string .= ($this->use_color ? "\e[0;31mERROR\e[0m" : 'ERROR');
        } else {
            $issue_string .= 'INFO';
        }

        $issue_string .= ': ' . $issue_data['type'] . ' - ' . $issue_data['file_name'] . ':' .
            $issue_data['line_from'] . ':' . $issue_data['column_from'] . ' - ' . $issue_data['message'] . "\n";

        if ($this->show_snippet) {
            $snippet = $issue_data['snippet'];

            if (!$this->use_color) {
                $issue_string .= $snippet;
            } else {
                $selection_start = $issue_data['from'] - $issue_data['snippet_from'];
                $selection_length = $issue_data['to'] - $issue_data['from'];

                $issue_string .= substr($snippet, 0, $selection_start)
                    . ($is_error ? "\e[97;41m" : "\e[30;47m") . substr($snippet, $selection_start, $selection_length)
                    . "\e[0m" . substr($snippet, $selection_length + $selection_start) . "\n";
            }
        }

        return $issue_string;
    }
}
