<?php

namespace Psalm\Internal\Analyzer;

use function str_pad;

use const STR_PAD_LEFT;

/**
 * @internal
 */
class IssueData
{
    /**
     * @var string
     */
    public string $severity;

    /**
     * @var int
     */
    public int $line_from;

    /**
     * @var int
     */
    public int $line_to;

    /**
     * @var string
     * @readonly
     */
    public string $type;

    /**
     * @var string
     * @readonly
     */
    public string $message;

    /**
     * @var string
     * @readonly
     */
    public string $file_name;

    /**
     * @var string
     * @readonly
     */
    public string $file_path;

    /**
     * @var string
     * @readonly
     */
    public string $snippet;

    /**
     * @var string
     * @readonly
     */
    public string $selected_text;

    /**
     * @var int
     */
    public int $from;

    /**
     * @var int
     */
    public int $to;

    /**
     * @var int
     */
    public int $snippet_from;

    /**
     * @var int
     */
    public int $snippet_to;

    /**
     * @var int
     * @readonly
     */
    public int $column_from;

    /**
     * @var int
     * @readonly
     */
    public int $column_to;

    /**
     * @var int
     */
    public int $error_level;

    /**
     * @var int
     * @readonly
     */
    public int $shortcode;

    /**
     * @var string
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
     * @var ?string
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
