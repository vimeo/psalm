<?php

namespace Psalm\Internal\Analyzer;

use function str_pad;

use const STR_PAD_LEFT;

/**
 * @internal
 */
class IssueData
{
    public string $severity;

    public int $line_from;

    public int $line_to;

    /**
     * @readonly
     */
    public string $type;

    /**
     * @readonly
     */
    public string $message;

    /**
     * @readonly
     */
    public string $file_name;

    /**
     * @readonly
     */
    public string $file_path;

    /**
     * @readonly
     */
    public string $snippet;

    /**
     * @readonly
     */
    public string $selected_text;

    public int $from;

    public int $to;

    public int $snippet_from;

    public int $snippet_to;

    /**
     * @readonly
     */
    public int $column_from;

    /**
     * @readonly
     */
    public int $column_to;

    public int $error_level;

    /**
     * @readonly
     */
    public int $shortcode;

    /**
     * @readonly
     */
    public string $link;

    /**
     * @var ?list<DataFlowNodeData|array{label: string, entry_path_type: string}>
     */
    public ?array $taint_trace = null;

    /**
     * @var ?list<DataFlowNodeData>
     */
    public ?array $other_references = null;

    /**
     * @readonly
     */
    public ?string $dupe_key = null;

    /**
     * @param ?list<DataFlowNodeData|array{label: string, entry_path_type: string}> $taint_trace
     * @param ?list<DataFlowNodeData> $other_references
     */
    public function __construct(
        string $severity,
        int $line_from,
        int $line_to,
        string $type,
        string $message,
        string $file_name,
        string $file_path,
        string $snippet,
        string $selected_text,
        int $from,
        int $to,
        int $snippet_from,
        int $snippet_to,
        int $column_from,
        int $column_to,
        int $shortcode = 0,
        int $error_level = -1,
        ?array $taint_trace = null,
        array $other_references = null,
        ?string $dupe_key = null
    ) {
        $this->severity = $severity;
        $this->line_from = $line_from;
        $this->line_to = $line_to;
        $this->type = $type;
        $this->message = $message;
        $this->file_name = $file_name;
        $this->file_path = $file_path;
        $this->snippet = $snippet;
        $this->selected_text = $selected_text;
        $this->from = $from;
        $this->to = $to;
        $this->snippet_from = $snippet_from;
        $this->snippet_to = $snippet_to;
        $this->column_from = $column_from;
        $this->column_to = $column_to;
        $this->shortcode = $shortcode;
        $this->error_level = $error_level;
        $this->link = $shortcode ? 'https://psalm.dev/' . str_pad((string) $shortcode, 3, "0", STR_PAD_LEFT) : '';
        $this->taint_trace = $taint_trace;
        $this->other_references = $other_references;
        $this->dupe_key = $dupe_key;
    }
}
