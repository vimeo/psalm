<?php

namespace Psalm\Report;

use Psalm\Report;

use function sprintf;

final class CheckstyleReport extends Report
{
    public function create(): string
    {
        $output = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

        $output .= '<checkstyle>' . "\n";

        foreach ($this->issues_data as $issue_data) {
            $message = sprintf(
                '%s: %s',
                $issue_data->type,
                $issue_data->message
            );

            $output .= '<file name="' . $this->xmlEncode($issue_data->file_name) . '">' . "\n";
            $output .= ' ';
            $output .= '<error';
            $output .= ' line="' . $issue_data->line_from . '"';
            $output .= ' column="' . $issue_data->column_from . '"';
            $output .= ' severity="' . $issue_data->severity . '"';
            $output .= ' message="' . $this->xmlEncode($message) . '"';
            $output .= '/>' . "\n";
            $output .= '</file>' . "\n";
        }

        $output .= '</checkstyle>' . "\n";

        return $output;
    }
}
