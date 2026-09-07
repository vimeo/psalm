<?php

declare(strict_types=1);

namespace Psalm\Report;

use Override;
use Psalm\Config;
use Psalm\Report;

use function strtoupper;

/**
 * @psalm-external-mutation-free
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
            $is_error = $issue_data->severity === Config::REPORT_ERROR;
            if ($is_error) {
                $severity = $this->use_color ? "\e[0;31mERROR\e[0m" : 'ERROR';
            } else {
                $severity = strtoupper($issue_data->severity);
            }

            $output .= $severity . ' ' . $issue_data->file_name . ':' . $issue_data->line_from
                . ':' . $issue_data->column_from . ' ' . $issue_data->type . ': ' . $issue_data->message . "\n";
        }

        return $output;
    }
}
