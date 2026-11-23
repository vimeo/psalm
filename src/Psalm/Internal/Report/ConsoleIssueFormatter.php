<?php

declare(strict_types=1);

namespace Psalm\Internal\Report;

use Psalm\Config;
use Psalm\Internal\Analyzer\DataFlowNodeData;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\FileLink;
use Psalm\Report;

use function basename;
use function strlen;
use function substr;

/**
 * Issue formatting shared by the console-style reports. A report can override
 * formatIssueHeader() and getFileReference() to change the header and the locations.
 *
 * @internal
 * @psalm-external-mutation-free
 * @psalm-require-extends Report
 */
trait ConsoleIssueFormatter
{
    /**
     * @param string $level_label printed between the severity label and the colon
     * @psalm-external-mutation-free
     */
    private function formatIssue(IssueData $issue_data, string $level_label = ''): string
    {
        $is_error = $issue_data->severity === Config::REPORT_ERROR;

        $issue_reference = $issue_data->link ? ' (see ' . $issue_data->link . ')' : '';

        $issue_string = $this->formatIssueHeader(
            $issue_data,
            $level_label,
            $this->escapeControlCharacters(self::getMessageWithCwe($issue_data)) . $issue_reference,
        );

        if ($issue_data->taint_trace !== null && $issue_data->taint_trace !== []) {
            $issue_string .= $this->getTaintSnippets($issue_data->taint_trace);
        } elseif ($this->show_snippet) {
            $snippet = $issue_data->snippet;

            if (!$this->use_color) {
                $issue_string .= $this->escapeControlCharacters($snippet);
            } else {
                $selection_start = $issue_data->from - $issue_data->snippet_from;
                $selection_length = $issue_data->to - $issue_data->from;

                $issue_string .= $this->escapeControlCharacters(substr($snippet, 0, $selection_start))
                    . ($is_error ? "\e[97;41m" : "\e[30;47m")
                    . $this->escapeControlCharacters(substr($snippet, $selection_start, $selection_length))
                    . "\e[0m"
                    . $this->escapeControlCharacters(substr($snippet, $selection_length + $selection_start)) . "\n";
            }
        }

        if ($issue_data->other_references !== null && $issue_data->other_references !== []) {
            if ($this->show_snippet) {
                $issue_string .= "\n";
            }

            $issue_string .= $this->getTaintSnippets($issue_data->other_references);
        }

        return $issue_string;
    }

    /**
     * @param string $message escaped message, followed by the issue link
     * @psalm-external-mutation-free
     */
    private function formatIssueHeader(IssueData $issue_data, string $level_label, string $message): string
    {
        return $this->getSeverityLabel($issue_data) . $level_label . ': ' . $issue_data->type
            . ' - ' . $this->getFileReference($issue_data)
            . ' - ' . $message . "\n";
    }

    /**
     * @param non-empty-list<DataFlowNodeData|array{label: string, entry_path_type: string}> $taint_trace
     * @psalm-external-mutation-free
     */
    private function getTaintSnippets(array $taint_trace): string
    {
        $snippets = '';

        foreach ($taint_trace as $node_data) {
            if ($node_data instanceof DataFlowNodeData) {
                $snippets .= '  ' . $this->escapeControlCharacters($node_data->label) . ' - '
                    . $this->getFileReference($node_data) . "\n";

                if ($this->show_snippet) {
                    $snippet = $node_data->snippet;

                    if (!$this->use_color) {
                        $snippets .= $this->escapeControlCharacters($snippet) . "\n\n";
                    } else {
                        $selection_start = $node_data->from - $node_data->snippet_from;
                        $selection_length = $node_data->to - $node_data->from;

                        $snippets .= $this->escapeControlCharacters(substr($snippet, 0, $selection_start))
                            . "\e[30;47m"
                            . $this->escapeControlCharacters(substr($snippet, $selection_start, $selection_length))
                            . "\e[0m"
                            . $this->escapeControlCharacters(substr($snippet, $selection_length + $selection_start))
                            . "\n\n";
                    }
                }
            } else {
                $snippets .= '  ' . $this->escapeControlCharacters($node_data['label']) . "\n";
                $snippets .= '    <no known location>' . "\n\n";
            }
        }

        return $snippets;
    }

    /**
     * @psalm-external-mutation-free
     */
    private function getFileReference(IssueData|DataFlowNodeData $data): string
    {
        $file_name = $this->escapeControlCharacters($data->file_name);
        $reference = $file_name . ':' . $data->line_from . ':' . $data->column_from;

        if (!$this->use_color) {
            return $reference;
        }

        $file_basename = basename($file_name);
        $file_path = substr($file_name, 0, -strlen($file_basename));

        $reference = $file_path
            . "\033[1;31m"
            . $file_basename . ':' . $data->line_from . ':' . $data->column_from
            . "\033[0m"
        ;

        if ($this->in_ci) {
            return $reference;
        }

        return FileLink::hyperlink($reference, $this->escapeControlCharacters($data->file_path), $data->line_from);
    }
}
