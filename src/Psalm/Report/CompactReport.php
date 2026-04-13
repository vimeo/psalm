<?php

declare(strict_types=1);

namespace Psalm\Report;

use Override;
use Psalm\Config;
use Psalm\Internal\Analyzer\DataFlowNodeData;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Report;

use function basename;
use function count;
use function implode;
use function in_array;
use function str_contains;
use function strtoupper;

/**
 * @psalm-external-mutation-free
 */
final class CompactReport extends Report
{
    /** @var list<string> */
    private const SYNTHETIC_LABELS = ['variable-use', 'arrayvalue-fetch', 'coalesce', 'concat'];

    /**
     * @psalm-mutation-free
     */
    #[Override]
    public function create(): string
    {
        $output = '';

        foreach ($this->issues_data as $issue_data) {
            $is_error = $issue_data->severity === Config::REPORT_ERROR;
            $prefix = $is_error ? '' : strtoupper($issue_data->severity) . ' ';

            $chain_parts = $issue_data->taint_trace !== null
                ? $this->buildChainParts($issue_data)
                : null;

            if ($chain_parts !== null && $chain_parts !== []) {
                $hop_count = count($chain_parts) - 1;
                $hop_label = $hop_count <= 1 ? 'direct' : (string) $hop_count;

                $output .= $prefix . $issue_data->file_name . ':' . $issue_data->line_from
                    . ':' . $issue_data->column_from . ' ' . $issue_data->type . ' [' . $hop_label . ']' . "\n";
                $output .= '  ' . implode(' → ', $chain_parts) . "\n";
            } else {
                $output .= $prefix . $issue_data->file_name . ':' . $issue_data->line_from
                    . ':' . $issue_data->column_from . ' ' . $issue_data->type . ': ' . $issue_data->message . "\n";
            }
        }

        return $output;
    }

    /**
     * @return list<string>
     * @psalm-mutation-free
     */
    private function buildChainParts(IssueData $issue_data): array
    {
        if ($issue_data->taint_trace === null) {
            return [];
        }

        $app_nodes = [];
        foreach ($issue_data->taint_trace as $trace) {
            if (!($trace instanceof DataFlowNodeData)) {
                continue; // Skip entry-type-only array nodes (no location)
            }
            if ($trace->line_from === 0) {
                continue; // Skip stubs
            }
            if (str_contains($trace->file_path, '/vendor/')) {
                continue; // Skip vendor nodes
            }
            if (in_array($trace->label, self::SYNTHETIC_LABELS, true)) {
                continue; // Skip Psalm-internal graph nodes
            }
            $app_nodes[] = $trace;
        }

        if ($app_nodes === []) {
            return [];
        }

        $sink_file = $issue_data->file_name;
        $parts = [];
        $last_idx = count($app_nodes) - 1;

        foreach ($app_nodes as $i => $node) {
            $label = $node->label;

            if ($i === 0) {
                // Source: annotate with location
                if ($node->file_name === $sink_file) {
                    $label .= '@' . $node->line_from;
                } else {
                    $label .= '@[' . basename($node->file_name) . ':' . $node->line_from . ']';
                }
            } elseif ($i === $last_idx) {
                // Sink: annotate with line number
                $label .= '@' . $node->line_from;
            }
            // Intermediate nodes: label only, no line numbers

            $parts[] = $label;
        }

        return $parts;
    }
}
