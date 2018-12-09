<?php
namespace Psalm\Output;

use Psalm\Config;
use Psalm\Output;

class Pylint extends Output
{
    /**
     * {{@inheritdoc}}
     */
    public function create(): string
    {
        $output = '';
        foreach ($this->issues_data as $issue_data) {
            $output .= $this->format($issue_data) . "\n";
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
        $message = sprintf(
            '%s: %s',
            $issue_data['type'],
            $issue_data['message']
        );

        if ($issue_data['severity'] === Config::REPORT_ERROR) {
            $code = 'E0001';
        } else {
            $code = 'W0001';
        }

        // https://docs.pylint.org/en/1.6.0/output.html doesn't mention what to do about 'column',
        // but it's still useful for users.
        // E.g. jenkins can't parse %s:%d:%d.
        $message = sprintf('%s (column %d)', $message, $issue_data['column_from']);
        $issue_string = sprintf(
            '%s:%d: [%s] %s',
            $issue_data['file_name'],
            $issue_data['line_from'],
            $code,
            $message
        );

        return $issue_string;
    }
}
